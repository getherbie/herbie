<?php

declare(strict_types=1);

namespace herbie\middlewares;

use herbie\Alias;
use herbie\HttpException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function herbie\str_route_from_url;
use function herbie\str_trailing_slash;

/**
 * Serves files from @media under the public media and download URLs.
 */
final readonly class FileMiddleware implements MiddlewareInterface
{
    /** @var array<string, string> */
    private const array INLINE_MIME_TYPES = [
        'avif' => 'image/avif',
        'css' => 'text/css',
        'gif' => 'image/gif',
        'ico' => 'image/vnd.microsoft.icon',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'js' => 'text/javascript',
        'json' => 'application/json',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'txt' => 'text/plain',
        'webp' => 'image/webp',
    ];

    /** @var array<string, string> */
    private const array DOWNLOAD_MIME_TYPES = [
        '7z' => 'application/x-7z-compressed',
        'bz2' => 'application/x-bzip2',
        'csv' => 'text/csv',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'epub' => 'application/epub+zip',
        'gz' => 'application/gzip',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'rar' => 'application/vnd.rar',
        'rtf' => 'application/rtf',
        'tar' => 'application/x-tar',
        'tgz' => 'application/gzip',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xml' => 'application/xml',
        'zip' => 'application/zip',
    ];

    /** @var array<int, array{route: string, attachment: bool, inlineOnly: bool}> */
    private array $routes;

    /** @var array<string, string> */
    private array $mimeTypes;

    private string|false $basePath;

    public function __construct(
        private Alias $alias,
        private StreamFactoryInterface $streamFactory,
        private ResponseFactoryInterface $responseFactory,
        string $mediaUrl,
        string $downloadUrl,
        string $webUrl,
    ) {
        $this->routes = [
            [
                'route' => str_trailing_slash(str_route_from_url($mediaUrl, $webUrl)),
                'attachment' => false,
                'inlineOnly' => true,
            ],
            [
                'route' => str_trailing_slash(str_route_from_url($downloadUrl, $webUrl)),
                'attachment' => true,
                'inlineOnly' => false,
            ],
        ];
        $this->mimeTypes = self::INLINE_MIME_TYPES + self::DOWNLOAD_MIME_TYPES;
        $this->basePath = realpath($this->alias->get('@media'));
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = (string)$request->getAttribute(PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_ROUTE, '');
        $mode = null;
        foreach ($this->routes as $candidate) {
            if (str_starts_with($route, $candidate['route'])) {
                $mode = $candidate;
                break;
            }
        }
        if ($mode === null) {
            return $handler->handle($request);
        }

        $relativePath = rawurldecode(substr($route, strlen($mode['route'])));
        $filepath = realpath($this->alias->get('@media/' . $relativePath));
        if (
            $relativePath === '' || $this->basePath === false || $filepath === false
            || !is_file($filepath)
            || !str_starts_with($filepath, $this->basePath . DIRECTORY_SEPARATOR)
        ) {
            throw HttpException::fileNotFound($relativePath);
        }

        $extension = strtolower((string)pathinfo($filepath, PATHINFO_EXTENSION));
        if ($mode['inlineOnly'] && !isset(self::INLINE_MIME_TYPES[$extension])) {
            throw HttpException::fileNotFound($relativePath);
        }
        $contentType = $this->mimeTypes[$extension] ?? 'application/octet-stream';

        $stream = $this->streamFactory->createStreamFromFile($filepath);
        $size = $stream->getSize() ?? filesize($filepath);
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', $contentType)
            ->withHeader('X-Content-Type-Options', 'nosniff');
        if ($size !== false) {
            $response = $response->withHeader('Content-Length', (string)$size);
        }
        if ($mode['attachment']) {
            $response = $response->withHeader('Content-Disposition', $this->attachmentHeader(basename($filepath)));
        }
        return $response->withBody($stream);
    }

    private function attachmentHeader(string $filename): string
    {
        $fallback = preg_replace('/[^\x20-\x7E]/', '_', $filename) ?? 'download';
        $fallback = str_replace(['"', '\\'], '_', $fallback);
        return 'attachment; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($filename);
    }
}
