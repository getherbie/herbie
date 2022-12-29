<?php

declare(strict_types=1);

namespace herbie\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CallableMiddleware implements MiddlewareInterface
{
    /**
     * The callable to proxy.
     * @var callable
     */
    private $callable;

    /**
     * Set up a callable middleware proxying the given callable.
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * Return the response produced by the callable.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return ($this->callable)($request, $handler);
    }
}
