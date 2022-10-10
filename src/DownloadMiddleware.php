<?php

declare(strict_types=1);

namespace herbie;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tebe\HttpFactory\HttpFactory;

final class DownloadMiddleware implements MiddlewareInterface
{
    private Alias $alias;

    private string $baseUrl;

    private string $storagePath;

    /**
     * DownloadMiddleware constructor.
     */
    public function __construct(Alias $alias, Config $config)
    {
        $this->alias = $alias;
        $this->baseUrl = rtrim($config->getAsString('baseUrl'), '/') . '/';
        $this->storagePath = rtrim($config->getAsString('storagePath'), '/') . '/';
    }

    /**
     * @throws HttpException
     * @throws SystemException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();

        // no download request? go to next middleware
        if (!$this->isDownloadRequest($uri)) {
            return $handler->handle($request);
        }

        $filepath = $this->getFilePath($uri);

        // no valid file? throw exception
        if (!is_file($filepath) || !is_readable($filepath)) {
            throw HttpException::fileNotFound($uri->getPath());
        }

        // everything ok, create response
        $httpFactory = HttpFactory::instance();
        $stream = $httpFactory->createStreamFromFile($filepath);
        $contentType = $this->determineContentType($filepath);
        return $httpFactory->createResponse()
            ->withHeader('Content-type', $contentType)
            ->withBody($stream);
    }

    private function isDownloadRequest(UriInterface $uri): bool
    {
        $uriPath = $uri->getPath();
        $pos = strpos($uriPath, $this->baseUrl);
        if ($pos === 0) {
            return true;
        }
        return false;
    }

    private function getFilePath(UriInterface $uri): string
    {
        $uriPath = $uri->getPath();
        $pos = strpos($uriPath, $this->baseUrl);
        if ($pos === 0) {
            $filePath = $this->storagePath . substr($uriPath, strlen($this->baseUrl));
            return $this->alias->get($filePath);
        }
        return '';
    }

    /**
     * @throws SystemException
     */
    private function determineContentType(string $filepath): string
    {
        $extension = (string)pathinfo($filepath, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'pdf':
                return 'application/pdf';
        }
        throw SystemException::serverError('No content-type found for ' . $extension);
    }
}
