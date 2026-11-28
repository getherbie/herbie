---
title: Plugins
layout: doc
---

# Plugins

Herbie comes with several system plugins in its `plugins` directory.
Plugins from other sources are installed with Composer or placed in the `site/extend/plugins` directory.

A system plugin needs no separate installation, but its dependencies do.
The following plugins use external libraries that are not part of Herbie, so install the library of each plugin that you enable:

- `markdown` needs `league/commonmark` `^2.10`
- `textile` needs `netcarver/textile` `^4.0`
- `rst` needs `doctrine/rst-parser` `^0.5.2`
- `imagine` needs `imagine/imagine` `^1.3`
- `contact` needs `phpmailer/phpmailer` `^6.9` and `altcha-org/altcha` `^2.4`

The system plugins `twig`, `search` and `dummy` have no further dependencies.

For example, install the library of the markdown plugin with:

~~~bash
$ composer require league/commonmark:^2.10
~~~

All other plugins are installed with the help of Composer.

~~~bash
$ composer require myvendor/myplugin
~~~

Plugins in the `site/extend/plugins` directory need no installation either.
Place the plugin directory there and enable it.

All plugins are activated in the configuration file with the `enabledPlugins` setting, a comma-separated list of plugin names.
It does not matter whether a plugin is a system, a Composer or a local plugin.

~~~php
<?php
return [
    'enabledPlugins' => 'markdown,contact,search,myplugin',
];
~~~

The plugins are loaded in the order of the list.
If two plugins define the same Twig function, filter or test, the plugin listed last wins.
If the name of a plugin exists as system, Composer and local plugin, the local plugin takes precedence over the Composer plugin, and the Composer plugin over the system plugin.
A name in the list that matches no plugin is logged as a warning.
If a plugin is enabled whose required Composer package is missing or does not match the declared version, Herbie throws an exception that names the package to install.

Plugin settings can also be customized in the configuration file as desired.

## Available plugins

The Twig functions and filters provided by system plugins are listed in the [Twig functions](doc/indepth/twig-functions#plugin-functions) and [Twig filters](doc/indepth/twig-filters#plugin-filters) references.

{{ snippet('@site/snippets/plugins.twig', {linkto:'doc/plugins'}) }}

If you have a useful plugin for Herbie, [let us know](/development).
Thanks in advance for your support!
