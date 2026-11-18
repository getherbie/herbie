---
title: Extending
layout: doc
---

# Extending

Herbie can be extended at several points in the application lifecycle.
You can add functionality in different ways, from simple PHP files in the filesystem to distributed Composer plugins.

## Extension points

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th>Extension point</th>
            <th>Typical use</th>
            <th>Difficulty</th>
            <th>Frequency</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Console command</td>
            <td>Provide functionality in the CLI</td>
            <td>Medium</td>
            <td>Rare</td>
        </tr>
        <tr>
            <td>Event listener</td>
            <td>React to system events</td>
            <td>Medium</td>
            <td>Rare</td>
        </tr>
        <tr>
            <td>Application middleware</td>
            <td>Modify HTTP requests and responses</td>
            <td>Medium</td>
            <td>Rare</td>
        </tr>
        <tr>
            <td>Route middleware</td>
            <td>Modify HTTP requests and responses for matching routes</td>
            <td>Medium</td>
            <td>Rare</td>
        </tr>
        <tr>
            <td>Twig filter</td>
            <td>Transform values in templates</td>
            <td>Very easy</td>
            <td>Frequent</td>
        </tr>
        <tr>
            <td>Twig global</td>
            <td>Provide a global value or helper object</td>
            <td>Very easy</td>
            <td>Frequent</td>
        </tr>
        <tr>
            <td>Twig function</td>
            <td>Generate content in templates</td>
            <td>Very easy</td>
            <td>Frequent</td>
        </tr>
        <tr>
            <td>Twig test</td>
            <td>Evaluate boolean conditions in templates</td>
            <td>Very easy</td>
            <td>Frequent</td>
        </tr>
    </tbody>
</table>

For details on specific extension points, see [Commands](doc/indepth/commands), [Event Listeners](doc/indepth/events), [Middlewares](doc/indepth/middlewares), [Twig Filters](doc/indepth/twig-filters), [Twig Functions](doc/indepth/twig-functions), [Twig Globals](doc/indepth/twig-globals), and [Twig Tests](doc/indepth/twig-tests).

## Extension methods

There are four ways to add extensions, with increasing flexibility and complexity.

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th>Method</th>
            <th>Difficulty</th>
            <th>Reusability</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Using the filesystem</td>
            <td>Very easy</td>
            <td>Low</td>
        </tr>
        <tr>
            <td>Using a programmatic approach</td>
            <td>Easy</td>
            <td>Low</td>
        </tr>
        <tr>
            <td>Using a plugin</td>
            <td>Medium</td>
            <td>Medium</td>
        </tr>
        <tr>
            <td>Using a distributed plugin</td>
            <td>High</td>
            <td>High</td>
        </tr>
    </tbody>
</table>

### Choosing a method

- **Filesystem**: Best for small, project-specific extensions that should be quick to add.
- **Programmatic**: Useful when extensions should be registered explicitly in `index.php`, for example together with custom bootstrap logic.
- **Plugin**: Suitable for grouping related extensions in one class, with optional configuration and dependency checks.
- **Distributed plugin**: Use this when an extension should be versioned, shared, and installed via Composer.

## Extending using the filesystem

With the filesystem approach, you add extensions by placing PHP files in the corresponding directories inside `site/extend`.

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th style="width:35%">Directory</th>
            <th style="width:65%">Description</th>
        </tr>
    </thead>
    <tbody>
    {% for data in query(site.data.site_dir_extended).where("name^=extend", "name!=extend/plugins") %}
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
            <th style="width:35%">Extension point</th>
            <th style="width:65%">Return value</th>
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
            <td><code>[name, callable]</code> or <code>[name, callable, options]</code></td>
        </tr>
        <tr>
            <td>Twig function</td>
            <td><code>[name, callable]</code> or <code>[name, callable, options]</code></td>
        </tr>
        <tr>
            <td>Twig global</td>
            <td><code>[name, value]</code></td>
        </tr>
        <tr>
            <td>Twig test</td>
            <td><code>[name, callable]</code> or <code>[name, callable, options]</code></td>
        </tr>
    </tbody>
</table>

### Console commands

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

### Event listeners

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

### Application middlewares

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

### Route middlewares

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

### Twig filters

To add a Twig filter, create a PHP file in `site/extend/twig_filters` that returns an array with the filter name and a callable.
The filter is registered automatically.

~~~php 
<?php

$twigFilter = function (string $string): string {
    return strrev($string);
};

return ['reverse', $twigFilter];
~~~ 

### Twig functions

To add a Twig function, create a PHP file in `site/extend/twig_functions` that returns an array with the function name and a callable.
The function is registered automatically.

~~~php 
<?php

$twigFunction = function (string $name): string {
    return "Hello {$name}!";
};

return ['hello', $twigFunction];
~~~

### Twig globals

To add a Twig global, create a PHP file in `site/extend/twig_globals` that returns an array with the global name and its value.
The global is registered automatically.

~~~php
<?php

return ['hello', 'world'];
~~~

### Twig tests

To add a Twig test, create a PHP file in `site/extend/twig_tests` that returns an array with the test name and a callable.
The test is registered automatically.

~~~php 
<?php

$twigTest = function (int $value): bool {
    return ($value % 2) !== 0;
};

return ['odd', $twigTest];
~~~

## Extending using a programmatic approach

With the programmatic approach, you register extensions in the `index.php` bootstrap file.
The `herbie\Application` class provides `add*` methods for this purpose.

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

### Console command

~~~php
class CustomCommand extends Command
{
    // see class definition above
}

$app->addConsoleCommand(CustomCommand::class);
~~~ 

### Event listener

~~~php
use herbie\events\TwigInitializedEvent;

$event = function (TwigInitializedEvent $event): void {
    // do something with $event
};

$app->addEventListener(TwigInitializedEvent::class, $event);
~~~

### Application middleware

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

### Route middleware

~~~php
// the route middleware can be the same as the application middleware

$app->addRouteMiddleware('^route/.*', $middleware);
~~~

### Twig filter

~~~php
$twigFilter = function (string $string): string {
    return strrev($string);
};

$app->addTwigFilter('reverse', $twigFilter);
~~~

### Twig global

~~~php
$twigGlobal = new My\Custom\DateTime();

$app->addTwigGlobal('datetime', $twigGlobal);
~~~

### Twig function

~~~php
$twigFunction = function (string $name): string {
    return "Hello {$name}!";
};

$app->addTwigFunction('hello', $twigFunction);
~~~

### Twig test

~~~php
$twigTest = function (int $value): bool {
    return ($value % 2) !== 0;
};

$app->addTwigTest('odd', $twigTest);
~~~

## Extending using a plugin

A plugin bundles extensions in a dedicated class.
This approach is more flexible than the filesystem and programmatic approaches:

- it keeps related extensions together in one place
- it can provide configuration and declare dependencies
- it has access to application services through dependency injection

A plugin is a directory with a PHP class that is marked with the `#[Plugin]` attribute.

~~~text
myplugin
└── MyPlugin.php
~~~

The first argument of the attribute is the name of the plugin, which is used in the `enabledPlugins` setting and as key `plugins.myplugin` in the configuration.
Herbie finds the class by scanning the PHP files in the plugin directory, one level deep, without executing them.
There is one plugin per directory.

The result of the search is cached in `site/runtime/cache/system/plugins.php`, but not in debug mode (environment variable `HERBIE_DEBUG`).
The cache is renewed if a plugin directory or a Composer plugin is added, removed or updated.
If you change an existing plugin file in a production environment, clear the cache with the command `clear-files`.

A plugin can have default values for its settings.
They are returned by an optional file `config.php` in the plugin directory and can be overridden in the configuration file `site/config/main.php`.

~~~text
myplugin
├── config.php
└── MyPlugin.php
~~~

The config file must look like this:

~~~php
<?php

return [
    'greeting' => 'Hello',
];
~~~

A plugin can declare the Composer packages it needs with the argument `requires` of the attribute, a list of packages with a version constraint.
The packages are checked when the plugin is enabled, packages of plugins that are not enabled are not checked.
If a package is missing or does not match the version constraint, Herbie throws an exception that tells what to install.

~~~php
#[Plugin('myplugin', requires: ['league/commonmark' => '^2.10'])]
class MyPlugin implements herbie\PluginInterface
~~~

The plugin itself is a PHP class with the following methods:

~~~php 
<?php

use herbie\Plugin;

#[Plugin('myplugin')]
class MyPlugin implements herbie\PluginInterface
{
    public function apiVersion(): int
    {
        return 2;
    }

    public function consoleCommands(): array
    {
        return [];
    }

    public function eventListeners(): array
    {
        return [];
    }

    public function applicationMiddlewares(): array
    {
        return [];
    }

    public function routeMiddlewares(): array
    {
        return [];
    }

    public function twigFilters(): array
    {
        return [];
    }

    public function twigGlobals(): array
    {
        return [];
    }

    public function twigFunctions(): array
    {
        return [];
    }

    public function twigTests(): array
    {
        return [];
    }
}
~~~

To install the plugin, place its directory in `site/extend/plugins`.

To enable the plugin, add it to the comma-separated `enabledPlugins` setting in `site/config/main.php`.

~~~php
<?php 
return [
    'enabledPlugins' => 'markdown,myplugin,textile',
];
~~~

A complete plugin class can look like this:

~~~php
<?php

use herbie\Plugin;
use herbie\events\TwigInitializedEvent;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
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

#[Plugin('myplugin')]
class MyPlugin implements herbie\PluginInterface
{
    public function apiVersion(): int
    {
        return 2;
    }

    public function consoleCommands(): array
    {
        return [
            CustomCommand::class
        ];
    }

    public function eventListeners(): array
    {
        $event = function (TwigInitializedEvent $event): void {
            // do something with $event
        };
        return [
            [TwigInitializedEvent::class, $event]
        ];
    }

    public function applicationMiddlewares(): array
    {
        $middleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
            // do something with the request
            $response = $handler->handle($request);
            // do something with the response
            return $response;
        };
        return [
            $middleware
        ];
    }

    public function routeMiddlewares(): array
    {
        $middleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
            // do something with the request
            $response = $handler->handle($request);
            // do something with the response
            return $response;
        };
        return [
            ['^route/.*', $middleware]
        ];
    }

    public function twigFilters(): array
    {
        $twigFilter = function (string $string): string {
            return strrev($string);
        };
        return [
            ['reverse', $twigFilter]
        ];
    }

    public function twigGlobals(): array
    {
        return [
            ['hello', 'world']
        ];
    }

    public function twigFunctions(): array
    {
        $twigFunction = function (string $name): string {
            return "Hello {$name}!";
        };
        return [
            ['hello', $twigFunction]
        ];
    }

    public function twigTests(): array
    {
        $twigTest = function (int $value): bool {
            return ($value % 2) !== 0;
        };
        return [
            ['odd', $twigTest]
        ];
    }
}
~~~

For a complete example, see the [dummy system plugin](https://github.com/getherbie/herbie/tree/3.x/plugins/dummy).

## Extending using a distributed plugin

To distribute a plugin, turn it into a Composer package.
Create a `composer.json` file in the plugin directory and set its type to `herbie-plugin`.

~~~json
{
    "name": "myvendor/myplugin",
    "type": "herbie-plugin",
    "require-dev": {
        "getherbie/herbie": "dev-3.x"
    }
}
~~~

See the [Composer documentation](https://getcomposer.org) for details on publishing packages to [Packagist](https://packagist.org).

After publishing, install the plugin in your project with Composer:

~~~bash
composer require myvendor/myplugin
~~~

Then enable the plugin in the configuration.

The `contact` and `search` plugins, which ship with Herbie, are good examples of distributed plugins.
