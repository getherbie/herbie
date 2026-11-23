<?php

declare(strict_types=1);

namespace herbie\middlewares;

use herbie\Alias;
use herbie\HttpException;
use herbie\SystemException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function herbie\str_trailing_slash;

final readonly class DownloadMiddleware implements MiddlewareInterface
{
    private string $route;
    private string $storagePath;

    /**
     * DownloadMiddleware constructor.
     */
    public function __construct(
        private Alias $alias,
        private StreamFactoryInterface $streamFactory,
        private ResponseFactoryInterface $responseFactory,
        array $config,
    ) {
        $this->route = str_trailing_slash((string)($config['route'] ?? ''));
        $this->storagePath = str_trailing_slash((string)($config['storagePath'] ?? ''));
    }

    /**
     * @throws HttpException
     * @throws SystemException
     */
    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $request->getAttribute(PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_ROUTE);

        // no download request? go to next middleware
        if (!$this->isDownloadRequest($route)) {
            return $handler->handle($request);
        }

        $filepath = $this->getFilePath($route);

        // no valid file? throw exception
        if (!is_file($filepath) || !is_readable($filepath)) {
            throw HttpException::fileNotFound($filepath);
        }

        // everything ok, create response
        $stream = $this->streamFactory->createStreamFromFile($filepath);
        $contentType = $this->determineContentType($filepath);
        return $this->responseFactory->createResponse()
            ->withHeader('Content-type', $contentType)
            ->withBody($stream);
    }

    private function isDownloadRequest(string $route): bool
    {
        $pos = strpos($route, $this->route);
        if ($pos === 0) {
            return true;
        }
        return false;
    }

    private function getFilePath(string $route): string
    {
        $pos = strpos($route, $this->route);
        if ($pos === 0) {
            $filePath = $this->storagePath . substr($route, strlen($this->route));
            return $this->alias->get($filePath);
        }
        return '';
    }

    /**
     * @throws SystemException
     */
    private function determineContentType(string $filepath): string
    {
        $extension = strtolower((string)pathinfo($filepath, PATHINFO_EXTENSION));
        return match ($extension) {
            '7z' => 'application/x-7z-compressed',
            'bz2' => 'application/x-bzip2',
            'csv' => 'text/csv',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'epub' => 'application/epub+zip',
            'gif' => 'image/gif',
            'gz', 'tgz' => 'application/gzip',
            'jpeg', 'jpg' => 'image/jpeg',
            'json' => 'application/json',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
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
            'txt' => 'text/plain',
            'webp' => 'image/webp',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xml' => 'application/xml',
            'zip' => 'application/zip',
            default => throw SystemException::serverError('No content-type found for ' . $extension),
        };
    }
}
