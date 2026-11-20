---
title: GitHub Gist
layout: doc
---

# GitHub Gist

The GitHub Gist plugin embeds a [GitHub Gist](https://gist.github.com) with a single Twig function.
It demonstrates how to register a **Twig function** and how to build HTML safely.

## Structure

~~~text
site/extend/plugins/gist/
└── GistPlugin.php
~~~

## Code

~~~php
<?php

declare(strict_types=1);

namespace herbie\plugins\gist;

use herbie\AbstractPlugin;
use herbie\Plugin;
use Twig\TwigFunction;

#[Plugin('gist')]
final class GistPlugin extends AbstractPlugin
{
    public function twigFunctions(): array
    {
        return [
            new TwigFunction('gist', $this->render(...), ['is_safe' => ['html']]),
        ];
    }

    public function render(string $id, string $file = ''): string
    {
        $src = 'https://gist.github.com/' . rawurlencode($id) . '.js';
        if ($file !== '') {
            $src .= '?file=' . rawurlencode($file);
        }
        return sprintf('<script src="%s"></script>', htmlspecialchars($src, ENT_QUOTES));
    }
}
~~~

## Usage

~~~php
'enabledPlugins' => 'twig,gist',
~~~

{% verbatim %}
~~~twig
{{ gist('aa5b9f8c', 'example.php') }}
~~~
{% endverbatim %}

`is_safe => ['html']` is required because the function returns a `<script>` tag.
Use `rawurlencode()` for values that end up in a URL and `htmlspecialchars()` for values that end up in an attribute.
Herbie 3 has no shortcode system, so a Twig function is the way to offer this feature in content.
