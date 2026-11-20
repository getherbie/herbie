---
title: Disqus Comments
layout: doc
---

# Disqus Comments

The Disqus Comments plugin adds a comment thread to a page.
This plugin demonstrates a **Twig function that renders a Twig template**, plus a configurable template path.

## Structure

~~~text
site/extend/plugins/disqus/
├── templates/
│   └── disqus.twig
├── config.php
└── DisqusPlugin.php
~~~

## Configuration defaults

~~~php
<?php

return [
    'template' => '@site/extend/plugins/disqus/templates/disqus.twig',
];
~~~

## Plugin class

~~~php
<?php

declare(strict_types=1);

namespace herbie\plugins\disqus;

use herbie\AbstractPlugin;
use herbie\Config;
use herbie\Plugin;
use herbie\TwigRenderer;
use Twig\TwigFunction;

#[Plugin('disqus')]
final class DisqusPlugin extends AbstractPlugin
{
    public function __construct(
        private readonly Config $config,
        private readonly TwigRenderer $twigRenderer,
    ) {}

    public function twigFunctions(): array
    {
        return [
            new TwigFunction('disqus', $this->render(...), ['is_safe' => ['html']]),
        ];
    }

    public function render(string $shortname): string
    {
        $template = $this->config->get('plugins.disqus.template');
        return $this->twigRenderer->renderTemplate($template, [
            'shortname' => $shortname,
        ]);
    }
}
~~~

## Template

{% verbatim %}
~~~twig
<div id="disqus_thread"></div>
<script>
    (function() {
        var d = document, s = d.createElement('script');
        s.src = 'https://{{ shortname|url_encode }}.disqus.com/embed.js';
        s.setAttribute('data-timestamp', +new Date());
        (d.head || d.body).appendChild(s);
    })();
</script>
<noscript>
    Please enable JavaScript to view the
    <a href="https://disqus.com/?ref_noscript">comments powered by Disqus</a>.
</noscript>
~~~
{% endverbatim %}

## Usage

~~~php
'enabledPlugins' => 'twig,disqus',
~~~

{% verbatim %}
~~~twig
{{ disqus('my-forum-shortname') }}
~~~
{% endverbatim %}

To change the markup, create your own template and point the configuration at it:

~~~php
'plugins' => [
    'disqus' => [
        'template' => '@site/extend/plugins/mytheme/disqus.twig',
    ],
],
~~~

Herbie 3 has no shortcode system, so the Twig function is called from a page or layout instead.
