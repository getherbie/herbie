---
title: Extending
layout: doc
---

# Extending

Depending on the use case extending Herbie CMS is really simple.
Basically, you can do this in four different ways from easy to difficult.

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

And for all these types, Herbie CMS provides the following extension points to change the flow of the application lifecycle.

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

## 1. Extending using the filesystem for the project

### Console commands

For adding a command you can create a PHP file in the directory `site/extends/commands` that returns a Command class.
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

For adding an event listener you can create a PHP file in the directory `site/extends/events` that returns an array{string, callable} with the name of the event and a valid PHP callback.
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

For adding an intercepting filter you can create a PHP file in the directory `site/extends/filters` that returns an array{string, callable} with the name of the filter and a valid PHP callback.
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

For adding an application middleware you can create a PHP file in the directory `site/extends/middlewares_app` that returns a valid PHP callback.
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

For adding an route middleware you can create a PHP file in the directory `site/extends/middlewares_app` that returns an array{string, callable} with a route regex expression and a valid PHP callback.
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

For adding a Twig filter you can create a PHP file in the directory `site/extends/twig_filters` that returns an array{string, callable} with the name of the filter and a valid PHP callback.
The filter is then registered automatically.

~~~php 
<?php

$twigFilter = function (string $string): string {
    return strrev($string);
};

return ['reverse', $twigFilter];
~~~ 

### Twig functions

For adding a Twig function you can create a PHP file in the directory `site/extends/twig_functions` that returns an array{string, callable} with the name of the function and a valid PHP callback.
The function is then registered automatically.

~~~php 
<?php

$twigFunction = function (string $name): string {
    return "Hello {$name}!";
};

return ['hello', $twigFunction];
~~~

### Twig globals

For adding Twig globals you can create a PHP file in the directory `site/extends/twig_globals` that returns an array<string, mixed>.
The array is then automatically merged recursively with the existing Twig globals.

~~~php
<?php

return [
    'hello' => 'world'
];
~~~

### Twig tests

For adding a Twig test you can create a PHP file in the directory `site/extends/twig_tests` that returns an array{string, callable} with the name of the test and a valid PHP callback.
The test is then registered automatically.

~~~php 
<?php

$twigTest = function (int $value): bool {
    return ($value % 2) !== 0;
}

return ['odd', $twigTest];
~~~

## 2. Extending using a programmatic approach

TBD

## 3. Extending using a plugin

TBD

## 4. Extending using a distributed plugin

TBD
