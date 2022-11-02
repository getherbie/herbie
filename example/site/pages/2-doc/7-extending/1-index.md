---
title: Extending
layout: doc
---

# Extending

Depending on the use case extending Herbie CMS is really simple.

Basically, it provides the following extension points to change the flow of the application lifecycle.

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th>How-to</th>
            <th>Difficulty</th>
            <th>How often?</th>
            <th>When?</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Console command</td>
            <td>Medium</td>
            <td>Rarely</td>
            <td>Access via CLI</td>
        </tr>
        <tr>
            <td>Event listener</td>
            <td>Medium</td>
            <td>Rarely</td>
            <td>Hook the system</td>
        </tr>
        <tr>
            <td>Intercepting filter</td>
            <td>Easy</td>
            <td>Frequently</td>
            <td>Value transformation</td>
        </tr>
        <tr>
            <td>Application middleware</td>
            <td>Medium</td>
            <td>Rarely</td>
            <td>Change HTTP request/response</td>
        </tr>
        <tr>
            <td>Route middleware</td>
            <td>Medium</td>
            <td>Rarely</td>
            <td>Change HTTP request/response</td>
        </tr>
        <tr>
            <td>Twig filter</td>
            <td>Very easy</td>
            <td>Frequently</td>
            <td>Value transformation</td>
        </tr>
        <tr>
            <td>Twig global</td>
            <td>Very easy</td>
            <td>Frequently</td>
            <td>Helper object</td>
        </tr>
        <tr>
            <td>Twig function</td>
            <td>Very easy</td>
            <td>Frequently</td>
            <td>Content generation</td>
        </tr>
        <tr>
            <td>Twig test</td>
            <td>Very easy</td>
            <td>Frequently</td>
            <td>Boolean decision</td>
        </tr>
    </tbody>
</table>

And changing the flow of the application lifecycle can be done in four different ways from easy to difficult.

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th style="width:1%">#</th>
            <th>Extension workflow</th>
            <th>Difficulty</th>
            <th>Reusability</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1</td>
            <td>Using the filesystem</td>
            <td>Very easy</td>
            <td>Low</td>
        </tr>
        <tr>
            <td>2</td>
            <td>Using a programmatic approach</td>
            <td>Easy</td>
            <td>Low</td>
        </tr>
        <tr>
            <td>3</td>
            <td>Using a plugin</td>
            <td>Medium</td>
            <td>Medium</td>
        </tr>
        <tr>
            <td>4</td>
            <td>Using a distributed plugin</td>
            <td>High</td>
            <td>High</td>
        </tr>
    </tbody>
</table>

## 1. Extending using the filesystem for the project

Using the file system means that we work in the `site` directory of the project.
More precisely, only simple PHP files need to be created and placed in the appropriate directories.

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th style="width:35%">Directory</th>
            <th style="width:65%">Description</th>
        </tr>
    </thead>
    <tbody>
    {% for data in site.data.site_dir_extended|filter("name^=extend")|filter("name!=extend/plugins") %}
        <tr>
            <td>site/{{ data.name }}</td>
            <td>{{ data.desc }}</td>
        </tr>
    {% endfor %}
    </tbody>
</table>

So, let's get started.

### Console commands

For adding a command you can create a PHP file in the directory `site/extend/commands` that returns a Command class.
The command is automatically registered and available in the CLI application.

~~~
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

### Event listeners

For adding an event listener you can create a PHP file in the directory `site/extend/events` that returns an array{string, callable} with the name of the event and a valid PHP callback.
The event is then registered automatically.

~~~php
<?php

use herbie\EventInterface;

$event = function (EventInterface $event): void {
    // do something with $event
};

return ['onTwigInitialized', $event];
~~~

### Intercepting filters

For adding an intercepting filter you can create a PHP file in the directory `site/extend/filters` that returns an array{string, callable} with the name of the filter and a valid PHP callback.
The filter is then registered automatically.

~~~php
<?php

use herbie\FilterInterface;

$filter = function (string $context, array $params, FilterInterface $filter): string {
    // do something with $context
    return $filter->next($context, $params, $filter);
};

return ['renderLayout', $filter];
~~~ 

### Application middlewares

For adding an application middleware you can create a PHP file in the directory `site/extend/middlewares_app` that returns a valid PHP callback.
The application middleware is then registered automatically.

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

### Route middlewares

For adding an route middleware you can create a PHP file in the directory `site/extend/middlewares_app` that returns an array{string, callable} with a route regex expression and a valid PHP callback.
The route middleware is then registered automatically.

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

return ['route/regex/expression', $middleware];
~~~

### Twig filters

For adding a Twig filter you can create a PHP file in the directory `site/extend/twig_filters` that returns an array{string, callable} with the name of the filter and a valid PHP callback.
The filter is then registered automatically.

~~~php 
<?php

$twigFilter = function (string $string): string {
    return strrev($string);
};

return ['reverse', $twigFilter];
~~~ 

### Twig functions

For adding a Twig function you can create a PHP file in the directory `site/extend/twig_functions` that returns an array{string, callable} with the name of the function and a valid PHP callback.
The function is then registered automatically.

~~~php 
<?php

$twigFunction = function (string $name): string {
    return "Hello {$name}!";
};

return ['hello', $twigFunction];
~~~

### Twig globals

For adding Twig globals you can create a PHP file in the directory `site/extend/twig_globals` that returns an array<string, mixed>.
The array is then automatically merged recursively with the existing Twig globals.

~~~php
<?php

return [
    'hello' => 'world'
];
~~~

### Twig tests

For adding a Twig test you can create a PHP file in the directory `site/extend/twig_tests` that returns an array{string, callable} with the name of the test and a valid PHP callback.
The test is then registered automatically.

~~~php 
<?php

$twigTest = function (int $value): bool {
    return ($value % 2) !== 0;
};

return ['odd', $twigTest];
~~~

## 2. Extending using a programmatic approach

With the programmatic approach, you can achieve exactly the same.
The difference is that you have to customize the `index.php` bootstrap file and add the extensions programmatically.
For this purpose there are several add methods available in the `herbie\Application` class.
A simplified bootstrap file would then look like this.

~~~php
<?php
 
use herbie\Application;
use herbie\ApplicationPaths;

$app = new Application(
    new ApplicationPaths(__DIR__)
);

// --> start adding your extensions

$app->addCommand();
$app->addEvent();
$app->addFilter();
$app->addAppMiddleware();
$app->addRouteMiddleware();
$app->addTwigFilter();
$app->addTwigFunction();
$app->addTwigGlobals();
$app->addTwigTest();

// <-- finish adding your extensions

$app->run();
~~~

So, in the end, you have to do exactly the same with the programmatic approach as you do with the file system approach.
In detail, it then looks like this.

Adding a console command:

~~~php
class CustomCommand extends Command
{
    // see class definition above
}

$app->addCommand(CustomCommand::class);
~~~ 

Adding an event listener:

~~~php
$event = function (herbie\EventInterface $event): void {
    // do something with $event
};

$app->addEvent('onTwigInitialized', $event);
~~~

Adding an intercepting filter:

~~~php
$filter = function (string $context, array $params, herbie\FilterInterface $filter): string {
    // do something with $context
    return $filter->next($context, $params, $filter);
};

$app->addFilter('renderLayout', $filter);
~~~

Adding an application middleware:

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

$app->addAppMiddleware($middleware);
~~~

Adding a route middleware:

~~~php
the route middleware is the same the application middleware

$app->addRouteMiddleware('route/to/page', $middleware);
~~~

Adding a Twig filter:

~~~php
$twigFilter = function (string $string): string {
    return strrev($string);
};

$app->addTwigFilter('reverse', $twigFilter);
~~~

Adding a Twig function:

~~~php
$twigFunction = function (string $name): string {
    return "Hello {$name}!";
};

$app->addTwigFunction('hello', $twigFunction);
~~~

Adding a Twig test:

~~~php
$twigTest = function (int $value): bool {
    return ($value % 2) !== 0;
};

$app->addTwigTest('odd', $twigTest);
~~~


## 3. Extending using a plugin

TBD

## 4. Extending using a distributed plugin

TBD
