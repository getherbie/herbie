---
title: Configuration
layout: doc
---

# Configuration

Herbie CMS uses a configuration file to hold settings.
These default configurations are defined in the [config/defaults.php](https://github.com/getherbie/herbie/blob/2.x/config/defaults.php) file.

The following variables are available in the configuration file as PHP constants as constant strings:

    APP_PATH
    SITE_PATH
    WEB_PATH
    WEB_URL

These constant strings are parsed at runtime.

## Override settings

All default settings can be overridden in a separate configuration file.
This file must be located at `site/config/main.php`.

If Herbie CMS finds a file with valid values, the default settings will be overwritten with them.

## Example

For a project, nice readable URLs and Twig debugging should be enabled.

For this purpose a file `site/config/main.php` is created with the following content:

~~~php
<?php

return [
    'components' => [
        'twigRenderer' => [
            'debug' => true
        ],
        'urlManager' => [
            'niceUrls' => true,
        ]
    ]
];
~~~

This overrides the desired settings.

## Dot notation

All configuration values can be accessed via a function with support for dot notation.

The following function calls:

{% verbatim %}
    {{ config.getAsString('charset') }}
    {{ config.getAsString('fileExtensions.pages') }}
    {{ config.getAsBool('components.virtualCorePlugin.enableTwigInLayoutFilter') }}
{% endverbatim %}

Return the following values:

    Charset = {{ config.getAsString('charset') }}
    Page extensions = {{ config.getAsString('fileExtensions.pages') }}
    Enable twig = {{ config.getAsBool('components.virtualCorePlugin.enableTwigInLayoutFilter') }}
