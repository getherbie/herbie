---
title: Register in index.php
layout: doc
---

# Register in index.php

Register extensions directly in the `index.php` bootstrap file.
The `herbie\Application` class provides `add*` methods for this purpose.
For Twig filters, functions and tests, these methods take the name, callable and optional options as separate arguments.
The Twig object form used by plugin classes and files in `site/extend` is not available in this bootstrap API.

A simplified bootstrap file looks like this:

~~~php
<?php

use herbie\Application;
use herbie\ApplicationPaths;
use herbie\events\TwigInitializedEvent;

$app = new Application(
    new ApplicationPaths(__DIR__)
);

// Register extensions here, for example:
// $app->addConsoleCommand(CustomCommand::class);
// $app->addEventListener(TwigInitializedEvent::class, $event);
// $app->addApplicationMiddleware($middleware);
// $app->addRouteMiddleware('^route/.*', $middleware);
// $app->addTwigFilter('reverse', $twigFilter);
// $app->addTwigFunction('hello', $twigFunction);
// $app->addTwigGlobal('datetime', $twigGlobal);
// $app->addTwigTest('odd', $twigTest);

$app->run();
~~~

The following examples show how to register each extension point.

## Console command

~~~php
// CustomCommand is the class shown on the previous page
$app->addConsoleCommand(CustomCommand::class);
~~~

## Event listener

~~~php
use herbie\events\TwigInitializedEvent;

$event = function (TwigInitializedEvent $event): void {
    // do something with $event
};

$app->addEventListener(TwigInitializedEvent::class, $event);
~~~

## Application middleware

~~~php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

$middleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
    // do something with the request
    $response = $handler->handle($request);
    // do something with the response
    return $response;
};

$app->addApplicationMiddleware($middleware);
~~~

## Route middleware

~~~php
// the route middleware can be the same as the application middleware

$app->addRouteMiddleware('^route/.*', $middleware);
~~~

## Twig filter

~~~php
$twigFilter = function (string $string): string {
    return strrev($string);
};

$app->addTwigFilter('reverse', $twigFilter);
~~~

## Twig global

~~~php
$twigGlobal = new My\Custom\DateTime();

$app->addTwigGlobal('datetime', $twigGlobal);
~~~

## Twig function

~~~php
$twigFunction = function (string $name): string {
    return "Hello {$name}!";
};

$app->addTwigFunction('hello', $twigFunction);
~~~

## Twig test

~~~php
$twigTest = function (int $value): bool {
    return ($value % 2) !== 0;
};

$app->addTwigTest('odd', $twigTest);
~~~
