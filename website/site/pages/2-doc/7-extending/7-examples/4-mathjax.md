---
title: MathJax Renderer
layout: doc
---

# MathJax Renderer

The MathJax Renderer plugin renders mathematical notation in the browser.
This plugin adds a **Twig filter** that wraps a formula in `$...$` and loads the JavaScript only when the filter is used for the first time.
It demonstrates config defaults, lazy asset loading and a local asset.

## Structure

~~~text
site/extend/plugins/mathjax/
├── assets/
│   └── mathjax.js
├── config.php
└── MathjaxPlugin.php
~~~

## Configuration defaults

`config.php` returns the default values. They can be overridden under `plugins.mathjax` in `site/config/main.php`.

~~~php
<?php

return [
    'cdnUrl' => 'https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js',
    'initJs' => '@site/extend/plugins/mathjax/assets/mathjax.js',
];
~~~

## Code

~~~php
<?php

declare(strict_types=1);

namespace herbie\plugins\mathjax;

use herbie\AbstractPlugin;
use herbie\Assets;
use herbie\Config;
use herbie\Plugin;
use Twig\TwigFilter;

#[Plugin('mathjax')]
final class MathjaxPlugin extends AbstractPlugin
{
    private bool $assetsAdded = false;

    public function __construct(
        private readonly Assets $assets,
        private readonly Config $config,
    ) {}

    public function twigFilters(): array
    {
        return [
            new TwigFilter('math', $this->math(...)),
        ];
    }

    public function math(string $content): string
    {
        if (!$this->assetsAdded) {
            $initJs = $this->config->get('plugins.mathjax.initJs');
            $cdnUrl = $this->config->get('plugins.mathjax.cdnUrl');
            if ($initJs) {
                $this->assets->addJs($initJs);
            }
            if ($cdnUrl) {
                $this->assets->addJs($cdnUrl);
            }
            $this->assetsAdded = true;
        }
        return '$' . $content . '$';
    }
}
~~~

The initialization file only configures MathJax; the script itself comes from the CDN:

~~~js
window.MathJax = {
    tex: {inlineMath: [['$', '$'], ['\\(', '\\)']]}
};
~~~

## Usage

~~~php
'enabledPlugins' => 'twig,markdown,mathjax',
~~~

{% verbatim %}
~~~twig no-test
The mass–energy equivalence is {{ 'E = mc^2'|math }}.
~~~
{% endverbatim %}

Assets are added while the content is rendered, before the layout is rendered.
The layout prints them with `js()` from the `twig` system plugin.
`@site` addresses local plugins; `@plugin` addresses the system plugins shipped with Herbie.
