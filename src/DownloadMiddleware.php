<?php

declare(strict_types=1);

namespace herbie;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
        $this->baseUrl = str_trailing_slash($config->getAsString('baseUrl'));
        $this->storagePath = str_trailing_slash($config->getAsString('storagePath'));
    }

    /**
     * @throws HttpException
     * @throws SystemException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pathInfo = $request->getAttribute(PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_PATH);

        if ($pathInfo === null) {
            throw new SystemException(sprintf(
                'Server Request Attribute "%s" not found',
                PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_PATH
            ));
        }

        // no download request? go to next middleware
        if (!$this->isDownloadRequest($pathInfo)) {
            return $handler->handle($request);
        }

        $filepath = $this->getFilePath($pathInfo);

        // no valid file? throw exception
        if (!is_file($filepath) || !is_readable($filepath)) {
            throw HttpException::fileNotFound($pathInfo);
        }

        // everything ok, create response
        $httpFactory = HttpFactory::instance();
        $stream = $httpFactory->createStreamFromFile($filepath);
        $contentType = $this->determineContentType($filepath);

        return $httpFactory->createResponse()
            ->withHeader('Content-Disposition', sprintf('attachment; filename="%s"', basename($filepath)))
            ->withHeader('Content-Length', $stream->getSize())
            ->withHeader('Content-Type', $contentType)
            ->withBody($stream);
    }

    private function isDownloadRequest(string $pathInfo): bool
    {
        $pos = strpos($pathInfo, $this->baseUrl);
        if ($pos === 0) {
            return true;
        }
        return false;
    }

    private function getFilePath(string $pathInfo): string
    {
        $pos = strpos($pathInfo, $this->baseUrl);
        if ($pos === 0) {
            $filePath = $this->storagePath . substr($pathInfo, strlen($this->baseUrl));
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
