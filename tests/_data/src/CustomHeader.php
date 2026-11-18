<?php

declare(strict_types=1);

namespace herbie\tests\_data\src;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CustomHeader implements MiddlewareInterface
{
    public function __construct(private readonly string $identifier) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute('X-Custom-Attribute-' . ucfirst($this->identifier), time());
        $response = $handler->handle($request);
        return $response->withHeader('X-Custom-Header-' . ucfirst($this->identifier), time());
    }
}
