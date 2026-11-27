---
title: Plugin class
layout: doc
---

# Plugin class

A plugin bundles extensions in a dedicated class.
This method is more flexible than adding files in `site/extend` or registering extensions in `index.php`:

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

A plugin can declare the Composer packages it needs with the argument `requires` of the attribute, either as `['vendor/package' => '^1.0']` with a version constraint or as `['vendor/package']` if any version is acceptable.
The packages are checked when the plugin is enabled, packages of plugins that are not enabled are not checked.
If a package is missing or does not match the version constraint, Herbie throws an exception that tells what to install.

~~~php
<?php

use herbie\Plugin;

#[Plugin('myplugin', requires: ['league/commonmark' => '^2.10'])]
class MyPlugin implements herbie\PluginInterface
{
}
~~~

The plugin itself is a PHP class with the following methods. A plugin normally extends `herbie\AbstractPlugin`, which provides empty defaults so only the needed methods must be overridden; the [example plugins](doc/extending/examples) use this base class. The skeleton below implements `PluginInterface` directly to list every available method.

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

`apiVersion()` declares the plugin API version; the current version is 2 and a lower value prevents the plugin from being loaded.

The Twig methods accept both arrays like the ones above and `TwigFilter`, `TwigFunction` or `TwigTest` objects. The [example plugins](doc/extending/examples) use the object form.

To install the plugin, place its directory in `site/extend/plugins`.

To enable the plugin, add it to the comma-separated `enabledPlugins` setting in `site/config/main.php`.

~~~php
<?php
return [
    'enabledPlugins' => 'markdown,myplugin,textile',
];
~~~
