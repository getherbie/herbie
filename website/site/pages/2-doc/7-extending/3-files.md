---
title: Files in site/extend
layout: doc
---

# Files in site/extend

Add extensions by placing PHP files in the corresponding directories inside `site/extend`.

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th>Directory</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
    {% for data in query(site.data.site_dir_extended).where("name^=extend", "name!=extend", "name!=extend/plugins") %}
        <tr>
            <td>site/{{ data.name }}</td>
            <td>{{ data.desc }}</td>
        </tr>
    {% endfor %}
    </tbody>
</table>

Each file must return the value expected by the extension point. Herbie discovers and registers these files automatically.

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th>Extension point</th>
            <th>Return value</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Console command</td>
            <td>Command class string</td>
        </tr>
        <tr>
            <td>Event listener</td>
            <td><code>[eventName, callable]</code> or <code>[eventName, callable, priority]</code></td>
        </tr>
        <tr>
            <td>Application middleware</td>
            <td>Middleware (callable, instance, or class string)</td>
        </tr>
        <tr>
            <td>Route middleware</td>
            <td><code>[routeRegex, middleware]</code></td>
        </tr>
        <tr>
            <td>Twig filter</td>
            <td><code>[name, callable]</code>, <code>[name, callable, options]</code> or <code>TwigFilter</code></td>
        </tr>
        <tr>
            <td>Twig function</td>
            <td><code>[name, callable]</code>, <code>[name, callable, options]</code> or <code>TwigFunction</code></td>
        </tr>
        <tr>
            <td>Twig global</td>
            <td><code>[name, value]</code></td>
        </tr>
        <tr>
            <td>Twig test</td>
            <td><code>[name, callable]</code>, <code>[name, callable, options]</code> or <code>TwigTest</code></td>
        </tr>
    </tbody>
</table>

For Twig filters, functions and tests, files in `site/extend` and plugin classes can also return the corresponding `TwigFilter`, `TwigFunction` or `TwigTest` object directly. The [example plugins](doc/extending/examples) use this form.

## Console commands

To add a console command, create a PHP file in `site/extend/commands` that returns a command class.
The command is registered automatically and becomes available in the CLI.

~~~php
<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CustomCommand extends Command
{
    protected static $defaultName = 'custom';
    protected static $defaultDescription = 'A custom command.';

    protected function configure(): void
    {
        $this->setHelp('This is a custom command.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Message from custom command.');
        return Command::SUCCESS;
    }
}

return CustomCommand::class;
~~~

## Event listeners

To add an event listener, create a PHP file in `site/extend/events` that returns an array with the event class name and a callable.
The listener is registered automatically.

~~~php
<?php

use herbie\events\TwigInitializedEvent;

$event = function (TwigInitializedEvent $event): void {
    // do something with $event
};

return [TwigInitializedEvent::class, $event];
~~~

## Application middlewares

To add an application middleware, create a PHP file in `site/extend/middlewares_app` that returns a middleware.
The middleware is registered automatically.

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

$middleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
    // do something with the request
    $response = $handler->handle($request);
    // do something with the response
    return $response;
};

return $middleware;
~~~

## Route middlewares

To add a route middleware, create a PHP file in `site/extend/middlewares_route` that returns an array with a route regular expression and a middleware.
The middleware is registered automatically.

~~~php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

$middleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
    // do something with the request
    $response = $handler->handle($request);
    // do something with the response
    return $response;
};

return ['^route/.*', $middleware];
~~~

## Twig filters

To add a Twig filter, create a PHP file in `site/extend/twig_filters` that returns an array with the filter name and a callable.
The filter is registered automatically.

~~~php
<?php

$twigFilter = function (string $string): string {
    return strrev($string);
};

return ['reverse', $twigFilter];
~~~

## Twig functions

To add a Twig function, create a PHP file in `site/extend/twig_functions` that returns an array with the function name and a callable.
The function is registered automatically.

~~~php
<?php

$twigFunction = function (string $name): string {
    return "Hello {$name}!";
};

return ['hello', $twigFunction];
~~~

## Twig globals

To add a Twig global, create a PHP file in `site/extend/twig_globals` that returns an array with the global name and its value.
The global is registered automatically.

~~~php
<?php

return ['hello', 'world'];
~~~

## Twig tests

To add a Twig test, create a PHP file in `site/extend/twig_tests` that returns an array with the test name and a callable.
The test is registered automatically.

~~~php
<?php

$twigTest = function (int $value): bool {
    return ($value % 2) !== 0;
};

return ['odd', $twigTest];
~~~
