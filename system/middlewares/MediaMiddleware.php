<?php

declare(strict_types=1);

namespace herbie\middlewares;

use herbie\Alias;
use herbie\FileRangeStream;
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
 * A single HTTP byte range (`Range: bytes=...`) is supported, e.g. for seeking in video and audio.
 * ETag, Last-Modified and Cache-Control are sent, conditional requests are answered with 304.
 *
 * The former route `download/...` is kept as a deprecated alias and redirects
 * to `<media>/...?download`.
 */
final readonly class MediaMiddleware implements MiddlewareInterface
{
    // @deprecated Since 3.2.0, remove in 4.0 together with the redirect in process().
    private const string LEGACY_DOWNLOAD_ROUTE = 'download';

    /**
     * Browsers and proxies may reuse a file for one day, then they ask again with ETag and Last-Modified.
     * Intentionally not "public": shared caches must not store responses to requests with an
     * Authorization header, e.g. media protected by HttpBasicAuthMiddleware (RFC 9111 section 3.5).
     */
    private const string CACHE_CONTROL = 'max-age=86400';

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

        $size = filesize($filepath);
        $mtime = filemtime($filepath);
        $etag = ($size !== false && $mtime !== false) ? sprintf('"%x-%x"', $mtime, $size) : null;
        $lastModified = $mtime !== false ? gmdate('D, d M Y H:i:s', $mtime) . ' GMT' : null;

        if ($etag !== null && $mtime !== false && $this->isNotModified($request, $etag, $mtime)) {
            return $this->notModified($etag, (string)$lastModified);
        }

        $range = $size === false ? null : $this->requestedRange($request, $size, $etag, $lastModified);
        if ($range === false) {
            return $this->rangeNotSatisfiable((int)$size);
        }

        $response = $this->responseFactory->createResponse($range === null ? 200 : 206)
            ->withHeader('Content-Type', $this->contentType($extension))
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Accept-Ranges', 'bytes')
            ->withHeader('Cache-Control', self::CACHE_CONTROL);
        if ($etag !== null) {
            $response = $response->withHeader('ETag', $etag);
        }
        if ($lastModified !== null) {
            $response = $response->withHeader('Last-Modified', $lastModified);
        }
        if ($size !== false) {
            $length = $range === null ? $size : $range[1] - $range[0] + 1;
            $response = $response->withHeader('Content-Length', (string)$length);
        }
        if ($range !== null) {
            $response = $response->withHeader('Content-Range', sprintf('bytes %d-%d/%d', $range[0], $range[1], $size));
        }
        if ($attachment) {
            $response = $response->withHeader('Content-Disposition', $this->attachmentHeader(basename($filepath)));
        }
        // a HEAD response has the same headers, but no body that would have to be read
        if (strtoupper($request->getMethod()) === 'HEAD') {
            return $response->withBody($this->streamFactory->createStream());
        }
        if ($range !== null) {
            return $response->withBody(new FileRangeStream($filepath, $range[0], $range[1] - $range[0] + 1));
        }
        return $response->withBody($this->streamFactory->createStreamFromFile($filepath));
    }

    /**
     * Evaluates the conditional headers If-None-Match and If-Modified-Since of a GET or HEAD request.
     * If-None-Match takes precedence, If-Modified-Since is only used without it.
     */
    private function isNotModified(ServerRequestInterface $request, string $etag, int $mtime): bool
    {
        if (!in_array(strtoupper($request->getMethod()), ['GET', 'HEAD'], true)) {
            return false;
        }

        $ifNoneMatch = trim($request->getHeaderLine('If-None-Match'));
        if ($ifNoneMatch !== '') {
            if ($ifNoneMatch === '*') {
                return true;
            }
            foreach (explode(',', $ifNoneMatch) as $candidate) {
                $candidate = trim($candidate);
                // weak comparison: the weak indicator is ignored
                if (str_starts_with($candidate, 'W/')) {
                    $candidate = substr($candidate, 2);
                }
                if ($candidate === $etag) {
                    return true;
                }
            }
            return false;
        }

        $ifModifiedSince = trim($request->getHeaderLine('If-Modified-Since'));
        if ($ifModifiedSince !== '') {
            $time = strtotime($ifModifiedSince);
            return $time !== false && $mtime <= $time;
        }

        return false;
    }

    private function notModified(string $etag, string $lastModified): ResponseInterface
    {
        return $this->responseFactory->createResponse(304)
            ->withHeader('ETag', $etag)
            ->withHeader('Last-Modified', $lastModified)
            ->withHeader('Cache-Control', self::CACHE_CONTROL);
    }

    /**
     * Evaluates the Range header. Only a single byte range is supported. Everything else, e.g. multiple
     * ranges or an invalid header, is ignored and the whole file is sent. The same applies if the validator
     * of an If-Range header does not match the file.
     *
     * @return array{int, int}|false|null The first and last byte, false if the range cannot be satisfied,
     *                                    null if the whole file has to be sent.
     */
    private function requestedRange(
        ServerRequestInterface $request,
        int $size,
        ?string $etag,
        ?string $lastModified,
    ): array|false|null {
        $header = trim($request->getHeaderLine('Range'));
        if ($header === '' || $size <= 0) {
            return null;
        }
        $ifRange = trim($request->getHeaderLine('If-Range'));
        // intentional: an exact match is required, also for a date (RFC 9110 section 13.1.5), unlike If-Modified-Since
        if ($ifRange !== '' && $ifRange !== $etag && $ifRange !== $lastModified) {
            return null;
        }
        // intentional: an invalid or unsupported header (other unit, several ranges) is ignored, see RFC 7233
        if (preg_match('/^bytes=(\d*)-(\d*)$/', $header, $matches) !== 1) {
            return null;
        }
        [, $first, $last] = $matches;

        if ($first === '') {
            // suffix range: the last n bytes
            if ($last === '') {
                // intentional: "bytes=-" is syntactically invalid and ignored
                return null;
            }
            $suffix = (int)$last;
            return $suffix === 0 ? false : [max(0, $size - $suffix), $size - 1];
        }

        $start = (int)$first;
        if ($last !== '' && (int)$last < $start) {
            // intentional: the last byte before the first byte is invalid and ignored
            return null;
        }
        if ($start >= $size) {
            return false;
        }
        $end = $last === '' ? $size - 1 : min((int)$last, $size - 1);
        return [$start, $end];
    }

    private function rangeNotSatisfiable(int $size): ResponseInterface
    {
        return $this->responseFactory->createResponse(416)
            ->withHeader('Accept-Ranges', 'bytes')
            ->withHeader('Content-Range', 'bytes */' . $size);
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
