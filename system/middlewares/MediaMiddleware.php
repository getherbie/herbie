<?php

declare(strict_types=1);

namespace herbie\middlewares;

use herbie\Alias;
use herbie\HttpException;
use herbie\UrlManager;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function herbie\str_route_from_url;

/**
 * Serves files from @media under the public media URL (`urls.media`).
 *
 * Types a browser can display are sent inline, everything else as attachment.
 * The query parameter `?download` forces an attachment for any file.
 *
 * The former route `download/...` is kept as a deprecated alias and redirects
 * to `<media>/...?download`.
 */
final readonly class MediaMiddleware implements MiddlewareInterface
{
    // @deprecated Since 3.2.0, remove in 4.0 together with the redirect in process().
    private const string LEGACY_DOWNLOAD_ROUTE = 'download';

    /** @var array<string, string> */
    private const array MIME_TYPES = [
        '7z' => 'application/x-7z-compressed',
        'avif' => 'image/avif',
        'bz2' => 'application/x-bzip2',
        'css' => 'text/css',
        'csv' => 'text/csv',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'epub' => 'application/epub+zip',
        'gif' => 'image/gif',
        'gz' => 'application/gzip',
        'ico' => 'image/vnd.microsoft.icon',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'js' => 'text/javascript',
        'json' => 'application/json',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'rar' => 'application/vnd.rar',
        'rtf' => 'application/rtf',
        'svg' => 'image/svg+xml',
        'tar' => 'application/x-tar',
        'tgz' => 'application/gzip',
        'txt' => 'text/plain',
        'webp' => 'image/webp',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xml' => 'application/xml',
        'zip' => 'application/zip',
    ];

    /**
     * Types a browser can display. SVG and HTML are deliberately not part of it (XSS),
     * they are always sent as attachment.
     *
     * @var list<string>
     */
    private const array INLINE_EXTENSIONS = [
        'avif', 'css', 'gif', 'ico', 'jpeg', 'jpg', 'js', 'json', 'mp3', 'mp4', 'pdf', 'png', 'txt', 'webp',
    ];

    /** Extensions of files that must never be delivered. */
    private const string BLOCKED_EXTENSIONS_PATTERN = '/^(php\d*|phtml|phar|phps|pht)$/i';

    private string $route;

    private string|false $basePath;

    public function __construct(
        private Alias $alias,
        private StreamFactoryInterface $streamFactory,
        private ResponseFactoryInterface $responseFactory,
        private UrlManager $urlManager,
        string $mediaUrl,
        string $webUrl,
    ) {
        $this->route = trim(str_route_from_url($mediaUrl, $webUrl), '/');
        $this->basePath = realpath($this->alias->get('@media'));
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = (string)$request->getAttribute(PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_ROUTE, '');

        // @deprecated Since 3.2.0, remove in 4.0: former download route, redirects to the media route.
        $legacyPrefix = self::LEGACY_DOWNLOAD_ROUTE . '/';
        if ($this->route !== '' && str_starts_with($route, $legacyPrefix) && strlen($route) > strlen($legacyPrefix)) {
            return $this->redirectToMedia(substr($route, strlen($legacyPrefix)));
        }
        // end of @deprecated

        $prefix = $this->route . '/';
        if ($this->route === '' || !str_starts_with($route, $prefix)) {
            return $handler->handle($request);
        }

        $relativePath = rawurldecode(substr($route, strlen($prefix)));
        $filepath = $this->resolveFile($relativePath);
        $extension = strtolower((string)pathinfo($filepath, PATHINFO_EXTENSION));

        $forceDownload = array_key_exists('download', $request->getQueryParams());
        $attachment = $forceDownload || !in_array($extension, self::INLINE_EXTENSIONS, true);

        $stream = $this->streamFactory->createStreamFromFile($filepath);
        $size = $stream->getSize() ?? filesize($filepath);
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', $this->contentType($extension))
            ->withHeader('X-Content-Type-Options', 'nosniff');
        if ($size !== false) {
            $response = $response->withHeader('Content-Length', (string)$size);
        }
        if ($attachment) {
            $response = $response->withHeader('Content-Disposition', $this->attachmentHeader(basename($filepath)));
        }
        return $response->withBody($stream);
    }

    /**
     * @throws HttpException
     */
    private function resolveFile(string $relativePath): string
    {
        if ($relativePath === '' || $this->basePath === false || str_contains($relativePath, "\0")) {
            throw HttpException::fileNotFound($relativePath);
        }

        // no hidden files or directories, no executable files
        foreach (explode('/', str_replace('\\', '/', $relativePath)) as $segment) {
            if (str_starts_with($segment, '.')) {
                throw HttpException::fileNotFound($relativePath);
            }
        }
        $extension = (string)pathinfo($relativePath, PATHINFO_EXTENSION);
        if (preg_match(self::BLOCKED_EXTENSIONS_PATTERN, $extension) === 1) {
            throw HttpException::fileNotFound($relativePath);
        }

        $filepath = realpath($this->alias->get('@media/' . $relativePath));
        if (
            $filepath === false
            || !is_file($filepath)
            || !is_readable($filepath)
            || !str_starts_with($filepath, $this->basePath . DIRECTORY_SEPARATOR)
        ) {
            throw HttpException::fileNotFound($relativePath);
        }
        return $filepath;
    }

    private function redirectToMedia(string $encodedPath): ResponseInterface
    {
        $location = $this->urlManager->createUrl($this->route . '/' . $encodedPath) . '?download';
        return $this->responseFactory->createResponse(301)->withHeader('Location', $location);
    }

    private function contentType(string $extension): string
    {
        $type = self::MIME_TYPES[$extension] ?? 'application/octet-stream';
        if (str_starts_with($type, 'text/') || $type === 'application/json') {
            $type .= '; charset=utf-8';
        }
        return $type;
    }

    private function attachmentHeader(string $filename): string
    {
        $fallback = preg_replace('/[^\x20-\x7E]/', '_', $filename) ?? 'download';
        $fallback = str_replace(['"', '\\'], '_', $fallback);
        return 'attachment; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($filename);
    }
}
