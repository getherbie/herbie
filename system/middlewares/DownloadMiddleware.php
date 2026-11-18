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
        $extension = (string)pathinfo($filepath, PATHINFO_EXTENSION);
        return match ($extension) {
            'pdf' => 'application/pdf',
            default => throw SystemException::serverError('No content-type found for ' . $extension),
        };
    }
}
