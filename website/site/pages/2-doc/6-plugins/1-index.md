---
title: Plugins
layout: doc
---

# Plugins

There are already some plugins available for Herbie.

Some plugins are part of Herbie, for example `markdown`, `textile`, `rest`, `imagine`, `simplecontact` and `simplesearch`.
These system plugins need no installation.
They are activated in the configuration file with the `enabledSysPlugins` setting.

~~~php
<?php
return [
    'enabledSysPlugins' => 'markdown,simplecontact,simplesearch',
];
~~~

All other plugins are installed with the help of Composer.

    $ composer require getherbie/plugin-disqus

After installation, these plugins are activated with the `enabledPlugins` setting.

~~~php
<?php
return [
    'enabledPlugins' => 'disqus',
];
~~~

Plugin settings can also be customized in the configuration file as desired.

## Available plugins

{{ snippet("@site/snippets/plugins.twig", {linkto:"doc/plugins"}) }}

If you have a useful plugin for Herbie, [let us know](/development).
Thanks in advance for your support!
