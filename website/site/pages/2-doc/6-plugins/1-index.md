---
title: Plugins
layout: doc
---

# Plugins

There are already some plugins available for Herbie.

Some plugins are part of Herbie, for example `markdown`, `textile`, `rest`, `imagine`, `simplecontact` and `simplesearch`.
These system plugins need no installation.

All other plugins are installed with the help of Composer.

    $ composer require getherbie/plugin-disqus

Plugins in the `site/extend/plugins` directory need no installation either.

All plugins are activated in the configuration file with the `enabledPlugins` setting, a comma-separated list of plugin names.
It does not matter whether a plugin is a system, a Composer or a local plugin.

~~~php
<?php
return [
    'enabledPlugins' => 'markdown,simplecontact,simplesearch,disqus',
];
~~~

The plugins are loaded in the order of the list.
If two plugins define the same Twig function, filter or test, the plugin listed last wins.
If the name of a plugin exists as system, Composer and local plugin, the local plugin takes precedence over the Composer plugin, and the Composer plugin over the system plugin.
A name in the list that matches no plugin is logged as a warning.

Plugin settings can also be customized in the configuration file as desired.

## Available plugins

{{ snippet("@site/snippets/plugins.twig", {linkto:"doc/plugins"}) }}

If you have a useful plugin for Herbie, [let us know](/development).
Thanks in advance for your support!
