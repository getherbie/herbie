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
    private array $composedMiddlewares;
    private array $middlewares;

    /**
     * Dispatcher constructor.
     */
    public function __construct(array $prependMiddlewares, array $appMiddlewares, array $routeMiddlewares, array $appendMiddlewares, string $route)
    {
        $this->middlewares = array_merge($prependMiddlewares, $appMiddlewares, $routeMiddlewares, $appendMiddlewares);
        $this->composedMiddlewares = $this->composeMiddlewares(
            $prependMiddlewares,
            $appMiddlewares,
            $routeMiddlewares,
            $appendMiddlewares,
            $route
        );
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $current = $this->getMiddleware();
        next($this->composedMiddlewares);
        return $current->process($request, $this);
    }

    public function getInfo(): array
    {
        $info = [];
        foreach ($this->middlewares as $middleware) {
            if (is_array($middleware) && (is_string($middleware[0]))) {
                $type = 'ROUTE';
                $callable = get_callable_name($middleware[1]);
            } else {
                $type = 'APP';
                $callable = get_callable_name($middleware);
            }
            $info[] = [
                $type,
                $callable[0],
                $callable[1],
            ];
        }        
        return $info;
    }
    
    private function getMiddleware(): MiddlewareInterface
    {
        $current = current($this->composedMiddlewares);

        if (is_string($current)) {
            $current = new $current();
        }

        if (is_callable($current)) {
            $current = new CallableMiddleware($current);
        }

        return $current;
    }

    private function composeMiddlewares(array $prependMiddlewares, array $appMiddlewares, array $routeMiddlewares, array $appendMiddlewares, string $route): array
    {
        if (empty($routeMiddlewares)) {
            return array_merge($prependMiddlewares, $appMiddlewares, $appendMiddlewares);
        }
        // append route middlewares to app middlewares depending on the matched route
        foreach ($routeMiddlewares as $routeMiddleware) {
            [$regex, $middleware] = $routeMiddleware;
            if (preg_match('#' . $regex . '#', $route)) {
                $appMiddlewares[] = $middleware;
            }
        }
        return array_merge($prependMiddlewares, $appMiddlewares, $appendMiddlewares);
    }
}
