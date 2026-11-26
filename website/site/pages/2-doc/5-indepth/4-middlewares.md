---
title: Middlewares
layout: doc
---

# Middlewares

Herbie implements the [PSR-15](https://www.php-fig.org/psr/psr-15/) middleware specification.
Each request passes through a chain of middlewares before it is answered.
A middleware can inspect or modify the request before the rest of the chain, and the response after it.

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CustomHeader implements MiddlewareInterface
{
    public function __construct(private string $header) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $response = $handler->handle($request);
        return $response->withHeader('X-Custom', $this->header);
    }
}
~~~

A middleware receives the PSR-7 request and a handler for the rest of the chain.
It calls `$handler->handle($request)` to continue with the next middleware, and it may return its own response instead to stop the chain.
A plain callable with the same signature is accepted as well, Herbie wraps it automatically.

## Application Middlewares

Application middlewares run for every request.
They are registered with the `Application` class.

~~~php
$app->addApplicationMiddleware(new CustomHeader('one'));

// a class name of a middleware without constructor arguments works as well
$app->addApplicationMiddleware(\herbie\middlewares\ResponseTimeMiddleware::class);
~~~

Application middlewares can also be added through a plugin with the `applicationMiddlewares()` method, or by placing a PHP file that returns a middleware in the `site/extend/middlewares_app` directory, one middleware per file.
Both approaches are described in [Extending](doc/extending).

The middleware that ships with Herbie for this purpose is the `herbie\middlewares\ResponseTimeMiddleware`.
It measures the request time and sets the header `X-Response-Time`.

### Global response headers

Herbie has no configuration option for response headers. Use an application middleware instead.
The following file sets `X-Robots-Tag` on every response, for example to keep a staging site out of search engines.

~~~php
<?php
// site/extend/middlewares_app/noindex.php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

return function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
    $response = $handler->handle($request);
    return $response->withHeader('X-Robots-Tag', 'noindex, nofollow');
};
~~~

## Route Middlewares

Route middlewares only run for requests that match a given route.
They are registered with a route regular expression and a middleware.

~~~php
$app->addRouteMiddleware('^news/', new HttpBasicAuthMiddleware(['user' => 'pass'], $app->getResponseFactory()));
~~~

The route is a regular expression that is matched against the request route with `preg_match`.
The expression is not anchored automatically, so start it with `^` if it must match at the beginning.
In the example above, only requests to routes below `news/` require authentication.

Route middlewares can also be added through a plugin with the `routeMiddlewares()` method, or by placing a PHP file in the `site/extend/middlewares_route` directory.
The file returns an array with the route regular expression and the middleware.

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

$middleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
    $response = $handler->handle($request);
    return $response->withHeader('X-Custom', 'route');
};

return ['news/(.+)', $middleware];
~~~

Both approaches are described in [Extending](doc/extending).

## Built-in Middlewares

Herbie runs its own middlewares before and after the ones you register.
The complete chain of a request looks like this:

1. `ErrorHandlerMiddleware`, catches exceptions of the whole chain and renders the error page
2. `PageResolverMiddleware`, resolves the page for the request route
3. your application middlewares, in registration order
4. your route middlewares, for matching routes, in registration order
5. `MediaMiddleware`, serves files from `@media` under the public media URL
6. `PageRendererMiddleware`, renders the page with Twig and outputs the response

The `PageResolverMiddleware` attaches the resolved page, route and route parameters to the request.
They are available in your middleware as request attributes.

~~~php
<?php

use herbie\middlewares\PageResolverMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class LogPage implements MiddlewareInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $page = $request->getAttribute(PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_PAGE);
        if ($page !== null) {
            $this->logger->info(sprintf('Page "%s" requested', $page->getRoute()));
        }
        return $handler->handle($request);
    }
}
~~~

The `PageResolverMiddleware` runs before your middlewares, so the page attribute is already set when your middleware is called.
