---
title: Video Embeds
layout: doc
---

# Video Embeds

The Video Embeds plugin adds responsive YouTube and Vimeo players to a page.
It demonstrates **two Twig functions sharing one configurable template**, including validation of the video ID.

## Structure

~~~text
site/extend/plugins/video/
├── templates/
│   └── video.twig
├── config.php
└── VideoPlugin.php
~~~

## Configuration defaults

~~~php
<?php

return [
    'template' => '@site/extend/plugins/video/templates/video.twig',
];
~~~

## Plugin class

~~~php
<?php

declare(strict_types=1);

namespace herbie\plugins\video;

use herbie\AbstractPlugin;
use herbie\Config;
use herbie\Plugin;
use herbie\TwigRenderer;
use InvalidArgumentException;
use Twig\TwigFunction;

#[Plugin('video')]
final class VideoPlugin extends AbstractPlugin
{
    private const array SOURCES = [
        'youtube' => 'https://www.youtube-nocookie.com/embed/',
        'vimeo' => 'https://player.vimeo.com/video/',
    ];

    public function __construct(
        private readonly Config $config,
        private readonly TwigRenderer $twigRenderer,
    ) {}

    public function twigFunctions(): array
    {
        return [
            new TwigFunction('youtube', $this->youtube(...), ['is_safe' => ['html']]),
            new TwigFunction('vimeo', $this->vimeo(...), ['is_safe' => ['html']]),
        ];
    }

    public function youtube(string $id, int $width = 640, int $height = 360, string $title = 'YouTube video'): string
    {
        return $this->render('youtube', $id, $width, $height, $title);
    }

    public function vimeo(string $id, int $width = 640, int $height = 360, string $title = 'Vimeo video'): string
    {
        return $this->render('vimeo', $id, $width, $height, $title);
    }

    private function render(string $provider, string $id, int $width, int $height, string $title): string
    {
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
            throw new InvalidArgumentException('Invalid video ID.');
        }

        $src = self::SOURCES[$provider] . rawurlencode($id);
        if ($provider === 'youtube') {
            $src .= '?rel=0';
        }

        return $this->twigRenderer->renderTemplate($this->config->get('plugins.video.template'), [
            'src' => $src,
            'width' => max(1, $width),
            'height' => max(1, $height),
            'title' => $title,
        ]);
    }
}
~~~

## Template

{% verbatim %}
~~~twig
<iframe
    src="{{ src }}"
    style="width: 100%; max-width: {{ width }}px; aspect-ratio: {{ width }} / {{ height }}; border: 0;"
    title="{{ title }}"
    loading="lazy"
    allowfullscreen
></iframe>
~~~
{% endverbatim %}

## Usage

Enable the plugin in `site/config/main.php`:

~~~php
'enabledPlugins' => 'twig,video',
~~~

Then call the functions from a page or layout:

{% verbatim %}
~~~twig
{{ youtube('_0TfPpjDkWU', 640, 360, 'Herbie introduction') }}
{{ vimeo('30459532', 640, 360, 'Herbie on Vimeo') }}
~~~
{% endverbatim %}

The template uses `aspect-ratio`, so the player scales with its container without additional CSS.
To change the markup, create your own template and point `plugins.video.template` at it.
