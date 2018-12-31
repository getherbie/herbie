<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareDispatcher implements RequestHandlerInterface
{
    /** @var MiddlewareInterface[] */
    protected $middlewares = [];

    /**
     * Dispatcher constructor.
     * @param MiddlewareInterface[] $middlewares
     */
    public function __construct(array $middlewares)
    {
        $this->middlewares = $middlewares;
    }

    public function dispatch(ServerRequestInterface $request) : ResponseInterface
    {
        $response = $this->handle($request);
        return $response;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $current = $this->getMiddleware();
        next($this->middlewares);
        $response = $current->process($request, $this);
        return $response;
    }

    protected function getMiddleware(): MiddlewareInterface
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
}
