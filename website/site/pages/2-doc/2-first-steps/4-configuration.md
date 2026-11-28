---
title: Configuration
layout: doc
---

# Configuration

Herbie uses a configuration file to hold settings.
These default configurations are defined in the [config/defaults.php](https://github.com/getherbie/herbie/blob/3.x/config/defaults.php) file.

The following variables are available in the configuration file as PHP constants as constant strings:

~~~text
APP_PATH
SITE_PATH
WEB_PATH
WEB_URL
~~~

These constant strings are parsed at runtime.

## Absolute URLs

The Twig helpers `page_url()`, `page_link()`, `file_url()` and `file_link()` create absolute URLs with `absolute: true`. By default they use the scheme and host of the current request. To use a fixed origin, set `urls.origin` to `scheme://host` without a path:

~~~php
<?php

return [
    'urls' => [
        'origin' => 'https://example.com',
    ],
];
~~~

Leave the value empty to keep the request-based behavior. The configured origin is also used by the special pages `sitemap.xml`, `robots.txt` and `atom.xml`, which render their URLs with `page_url(absolute: true)`.

## Override settings

All default settings can be overridden in a separate configuration file.
This file must be located at `site/config/main.php`.

If only `site/config/main.dist.php` is present (the shipped template with placeholders), copy it to `site/config/main.php` and fill in your own values.

If Herbie finds a file with valid values, the default settings will be overwritten with them.

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
~~~twig
{{ site.config.as_string('charset') }}
{{ site.config.as_string('fileExtensions.pages') }}
{{ site.config.as_bool('plugins.CORE.enableTwigInLayoutFilter') }}
~~~
{% endverbatim %}

Return the following values:

~~~text
Charset = {{ site.config.as_string('charset') }}
Page extensions = {{ site.config.as_string('fileExtensions.pages') }}
Enable twig = {{ site.config.as_bool('plugins.CORE.enableTwigInLayoutFilter') }}
~~~

The getters with a `get` prefix, such as `get_as_string`, work the same way.
