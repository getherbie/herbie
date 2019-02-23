<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tebe\HttpFactory\HttpFactory;

class DownloadMiddleware implements MiddlewareInterface
{

    /**
     * @var Configuration
     */
    private $config;
    /**
     * @var Alias
     */
    private $alias;

    /**
     * @var array
     */
    private $mapping;

    /**
     * DownloadMiddleware constructor.
     * @param Alias $alias
     * @param Configuration $config
     */
    public function __construct(Alias $alias, Configuration $config)
    {
        $this->alias = $alias;
        $this->config = $config;
        $this->mapping = [
            '/download/' => '@site/media/'
        ];
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
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
        $response = $httpFactory->createResponse(200)
            ->withHeader('Content-type', $contentType)
            ->withBody($stream);
        return $response;
    }

    /**
     * @param UriInterface $uri
     * @return bool
     */
    private function isDownloadRequest(UriInterface $uri)
    {
        $uriPath = $uri->getPath();
        foreach (array_keys($this->mapping) as $prefix) {
            $pos = strpos($uriPath, $prefix);
            if ($pos === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param UriInterface $uri
     * @return string
     * @throws SystemException
     */
    private function getFilePath(UriInterface $uri)
    {
        $uriPath = $uri->getPath();
        foreach ($this->mapping as $prefix => $alias) {
            $pos = strpos($uriPath, $prefix);
            if ($pos === 0) {
                 $filePath = $alias . substr($uriPath, strlen($prefix));
                 return $this->alias->get($filePath);
            }
        }
        throw SystemException::fileNotExist($uriPath);
    }

    /**
     * @param string $filepath
     * @return string
     * @throws SystemException
     */
    private function determineContentType(string $filepath)
    {
        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'pdf':
                return 'application/pdf';
        }
        throw SystemException::serverError('No content-type found for ' . $extension);
    }
}
