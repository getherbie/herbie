---
title: Lipsum Generator
layout: doc
---

# Lipsum Generator

The Lipsum Generator plugin creates placeholder text and images.
It is the most complex example here: it uses a **helper class**, several Twig functions and an external image service.

## Structure

~~~text
site/extend/plugins/lipsum/
├── classes/
│   └── LoremIpsum.php
└── LipsumPlugin.php
~~~

## Helper class

Local plugins are not part of Composer's autoloader, so the plugin file loads the helper explicitly.

~~~php
<?php

declare(strict_types=1);

namespace herbie\plugins\lipsum;

final class LoremIpsum
{
    private const array WORDS = [
        'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing',
        'elit', 'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'labore',
        'dolore', 'magna', 'aliqua', 'enim', 'ad', 'minim',
    ];

    public function sentence(int $length = 10): string
    {
        $words = [];
        for ($i = 0; $i < $length; $i++) {
            $words[] = self::WORDS[array_rand(self::WORDS)];
        }
        return ucfirst(implode(' ', $words)) . '.';
    }

    public function text(int $sentences = 3): string
    {
        $result = '';
        for ($i = 0; $i < $sentences; $i++) {
            $result .= ($result === '' ? '' : ' ') . $this->sentence();
        }
        return $result;
    }
}
~~~

## Plugin class

~~~php
<?php

declare(strict_types=1);

namespace herbie\plugins\lipsum;

use herbie\AbstractPlugin;
use herbie\Plugin;
use Twig\TwigFunction;

require_once __DIR__ . '/classes/LoremIpsum.php';

#[Plugin('lipsum')]
final class LipsumPlugin extends AbstractPlugin
{
    private readonly LoremIpsum $loremIpsum;

    public function __construct()
    {
        $this->loremIpsum = new LoremIpsum();
    }

    public function twigFunctions(): array
    {
        return [
            new TwigFunction('lipsum_text', $this->text(...)),
            new TwigFunction('lipsum_title', $this->title(...)),
            new TwigFunction('lipsum_image', $this->image(...), ['is_safe' => ['html']]),
        ];
    }

    public function text(int $sentences = 3): string
    {
        return $this->loremIpsum->text($sentences);
    }

    public function title(): string
    {
        return rtrim($this->loremIpsum->sentence(4), '.');
    }

    public function image(int $width = 200, int $height = 200, string $seed = ''): string
    {
        $url = 'https://picsum.photos/';
        if ($seed !== '') {
            $url .= 'seed/' . rawurlencode($seed) . '/';
        }
        $url .= $width . '/' . $height;

        return sprintf(
            '<img src="%s" width="%d" height="%d" alt="">',
            htmlspecialchars($url, ENT_QUOTES),
            $width,
            $height,
        );
    }
}
~~~

## Usage

~~~php
'enabledPlugins' => 'twig,lipsum',
~~~

{% verbatim %}
~~~twig
<h2>{{ lipsum_title() }}</h2>
<p>{{ lipsum_text(2) }}</p>
{{ lipsum_image(600, 400, 'herbie') }}
~~~
{% endverbatim %}

Only the plugin file is loaded by Herbie. Additional classes in the plugin directory need a `require_once`,
unless the plugin is a Composer package with its own autoloader.
The image service `lorempixel.com` no longer exists, so this example uses `picsum.photos` instead.
