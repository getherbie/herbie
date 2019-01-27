<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (php_sapi_name() == 'cli-server') {
    if (preg_match('/\.(?:js|css)$/', $_SERVER["REQUEST_URI"])) {
        return false;
    }
}

require_once(__DIR__ . '/../../vendor/autoload.php');

use Herbie\Middleware\HttpBasicAuthMiddleware;
use Herbie\Middleware\ResponseTimeMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

define('HERBIE_DEBUG', true);

class CustomHeader implements MiddlewareInterface
{

    private $count;

    public function __construct($count = 1)
    {
        $this->count = $count;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute('X-Custom-Attribute-' . $this->count, time());
        $response = $handler->handle($request);
        return $response->withHeader('X-Custom-Header-' . $this->count, time());
    }
}

$app = new Herbie\Application('../site', '../../vendor');

// Cache
// $fileCache = new Anax\Cache\FileCache();
// $fileCache->setPath(dirname(__DIR__) . '/site/runtime/cache/page/');
// $app->setPageCache($fileCache);

// Middlewares
$app->addMiddleware(ResponseTimeMiddleware::class);
$app->addMiddleware(CustomHeader::class);
$app->addMiddleware(new CustomHeader(2));
$app->addMiddleware(function (ServerRequestInterface $request, RequestHandlerInterface $next) {
    $request = $request->withAttribute('X-Custom-Attribute-3', time());
    $response = $next->handle($request);
    return $response->withHeader('X-Custom-Header-3', time());
});
$app->addMiddleware('blog/2015-07-30', function (ServerRequestInterface $request, RequestHandlerInterface $next) {
    $request = $request->withAttribute('X-Custom-Attribute-BLOG', time());
    $response = $next->handle($request);
    return $response->withHeader('X-Custom-Header-BLOG', time());
});
$app->addMiddleware('features', function (ServerRequestInterface $request, RequestHandlerInterface $next) {
    $request = $request->withAttribute('X-Custom-Attribute-FEATURES', time());
    $response = $next->handle($request);
    return $response->withHeader('X-Custom-Header-FEATURES', time());
});
$app->addMiddleware('news/january', new HttpBasicAuthMiddleware(['user' => 'pass']));

// Twig
$app->addTwigFunction(new Twig_Function('myfunction', function () {
    return 'My Function';
}));
$app->addTwigFilter(new Twig_Filter('myfilter', function () {
    return 'My Filter';
}));
$app->addTwigTest(new Twig_Test('mytest', function () {
    return true;
}));

// Run
$app->run();
