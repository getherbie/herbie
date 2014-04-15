<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (is_null($this) || ('Herbie\Application' != get_class($this))) {
    die('Not allowed to access this file.');
}

return [
    'app' => [
        'path' => $this['appPath']
    ],
    'site' => [
        'path' => $this['sitePath']
    ],
    'layouts' => [
        'path' => $this['sitePath'] . '/layouts'
    ],
    'pages' => [
        'path' => $this['sitePath'] . '/pages',
        'extensions' => ['txt', 'md', 'markdown', 'textile', 'htm', 'html', 'rss', 'xml']
    ],
    'posts' => [
        'path' => $this['sitePath'] . '/posts',
        'extensions' => ['txt', 'md', 'markdown', 'textile', 'htm', 'html', 'rss', 'xml'],
        'blogRoute' => 'blog'
    ],
    'data' => [
        'path' => $this['sitePath'] . '/data',
        'extensions' => ['yml', 'yaml']
    ],
    'nice_urls' => false,
    'twig' => [
        'debug' => false,
        'cache' => false, //$this['sitePath'] . '/cache/twig',
        'extend' => [
            'functions' => $this['sitePath'] . '/plugins/twig/functions',
            'filters' => $this['sitePath'] . '/plugins/twig/filters',
            'tests' => $this['sitePath'] . '/plugins/twig/tests',
        ]
    ],
    'cache' => [
        'page' => [
            'enable' => false,
            'dir' => $this['sitePath'] . '/cache/page',
            'expire' => 18000
        ],
        'data' => [
            'enable' => false,
            'dir' => $this['sitePath'] . '/cache/data',
            'expire' => 18000
        ]
    ],
    'pseudo_html' => [
        'from' => '<box>|</box>',
        'to'   => '<div class="box" markdown="1">|</div>',
    ],
    'language' => 'de',
    'locale' => 'de_DE.UTF-8',
    'charset' => 'UTF-8',
];
