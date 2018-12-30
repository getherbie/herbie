<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Herbie\Middleware\ResponseTimeMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

require_once(__DIR__ . '/../vendor/autoload.php');

define('HERBIE_DEBUG', true);

class CustomHeader implements MiddlewareInterface {

    protected $count;

    public function __construct($count = 1)
    {
        $this->count = $count;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $request = $request->withAttribute('X-Custom-Attribute-' . $this->count, time());
        $response = $handler->handle($request);
        return $response->withHeader('X-Custom-Header-' . $this->count, time());
    }
}

$app = new Herbie\Application('../site');
$app->setMiddleware([
    ResponseTimeMiddleware::class,
    CustomHeader::class,
    new CustomHeader(2),
    function(ServerRequestInterface $request, RequestHandlerInterface $next) {
        $request = $request->withAttribute('X-Custom-Attribute-3', time());
        $response = $next->handle($request);
        return $response->withHeader('X-Custom-Header-3', time());
    },
]);
$app->run();
