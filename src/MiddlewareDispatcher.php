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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareDispatcher implements RequestHandlerInterface
{
    /** @var MiddlewareInterface[] */
    private $middlewares = [];

    /**
     * Dispatcher constructor.
     * @param array $appMiddlewares
     * @param array $routeMiddleware
     * @param string $route
     */
    public function __construct(array $appMiddlewares, array $routeMiddleware, string $route)
    {
        $this->middlewares = $this->composeMiddlewares($appMiddlewares, $routeMiddleware, $route);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request) : ResponseInterface
    {
        $response = $this->handle($request);
        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $current = $this->getMiddleware();
        next($this->middlewares);
        $response = $current->process($request, $this);
        return $response;
    }

    /**
     * @return MiddlewareInterface
     */
    private function getMiddleware(): MiddlewareInterface
    {
        $current = current($this->middlewares);

        if (is_string($current)) {
            $current = new $current();
        }

        if (is_callable($current)) {
            $current = new CallableMiddleware($current);
        }

        return $current;
    }

    /**
     * @param array $appMiddlewares
     * @param array $routeMiddlewares
     * @param string $route
     * @return array
     */
    private function composeMiddlewares(array $appMiddlewares, array $routeMiddlewares, string $route): array
    {
        if (empty($routeMiddlewares)) {
            return $appMiddlewares;
        }
        $pageRendererMiddleware = array_pop($appMiddlewares);
        foreach ($routeMiddlewares as $regex => $middleware) {
            if (preg_match('#' . $regex . '#', $route)) {
                $appMiddlewares[] = $middleware;
            }
            /*
            if (substr($middlewareRoute, -1) === '*') {
                if (strpos($route, substr($middlewareRoute, 0, -1)) === 0) {
                    $appMiddlewares[] = $middleware;
                }
            } else {
                if ($route === $middlewareRoute) {
                    $appMiddlewares[] = $middleware;
                }
            }
            */
        }
        $appMiddlewares[] = $pageRendererMiddleware;
        return $appMiddlewares;
    }
}
