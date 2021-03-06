<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace example;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CustomHeader implements MiddlewareInterface
{

    private $identifier;

    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute('X-Custom-Attribute-' . ucfirst($this->identifier), time());
        $response = $handler->handle($request);
        return $response->withHeader('X-Custom-Header-' . ucfirst($this->identifier), time());
    }
}
