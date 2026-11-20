---
title: Composer package
layout: doc
---

# Composer package

To share a plugin with others, turn it into a Composer package.
Create a `composer.json` file in the plugin directory and set its type to `herbie-plugin`.

~~~json
{
    "name": "myvendor/myplugin",
    "type": "herbie-plugin",
    "require-dev": {
        "getherbie/herbie": "^3.0"
    }
}
~~~

See the [Composer documentation](https://getcomposer.org) for details on publishing packages to [Packagist](https://packagist.org).

After publishing, install the plugin in your project with Composer:

~~~bash
composer require myvendor/myplugin
~~~

Then enable the plugin in the configuration. Use the name from its `#[Plugin]` attribute in `enabledPlugins`; it can differ from the Composer package name.
