<?php

declare(strict_types=1);

namespace herbie;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewareDispatcher implements RequestHandlerInterface
{
    /** @var MiddlewareInterface[]|string[] */
    private array $middlewares;

    /**
     * Dispatcher constructor.
     */
    public function __construct(array $appMiddlewares, array $routeMiddleware, string $route)
    {
        $this->middlewares = $this->composeMiddlewares($appMiddlewares, $routeMiddleware, $route);
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $current = $this->getMiddleware();
        next($this->middlewares);
        return $current->process($request, $this);
    }

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
