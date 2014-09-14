<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (is_null($app) || ('Herbie\Application' != get_class($app))) {
    die('Not allowed to access this file.');
}

return [
    'app' => [
        'path' => $app['appPath']
    ],
    'site' => [
        'path' => $app['sitePath']
    ],
    'layouts' => [
        'path' => $app['sitePath'] . '/layouts'
    ],
    'theme' => 'default',
    'pages' => [
        'path' => $app['sitePath'] . '/pages',
        'extensions' => ['txt', 'md', 'markdown', 'textile', 'htm', 'html', 'rss', 'xml']
    ],
    'posts' => [
        'path' => $app['sitePath'] . '/posts',
        'extensions' => ['txt', 'md', 'markdown', 'textile', 'htm', 'html', 'rss', 'xml'],
        'blogRoute' => 'blog'
    ],
    'data' => [
        'path' => $app['sitePath'] . '/data',
        'extensions' => ['yml', 'yaml']
    ],
    'nice_urls' => false,
    'twig' => [
        'debug' => false,
        'cache' => false, //$app['sitePath'] . '/cache/twig',
        'extend' => [
            'functions' => $app['sitePath'] . '/plugins/twig/functions',
            'filters' => $app['sitePath'] . '/plugins/twig/filters',
            'tests' => $app['sitePath'] . '/plugins/twig/tests',
        ]
    ],
    'cache' => [
        'page' => [
            'enable' => false,
            'dir' => $app['sitePath'] . '/cache/page',
            'expire' => 86400
        ],
        'data' => [
            'enable' => false,
            'dir' => $app['sitePath'] . '/cache/data',
            'expire' => 86400
        ]
    ],
    'language' => 'de',
    'locale' => 'de_DE.UTF-8',
    'charset' => 'UTF-8',
    'plugins_path' => $app['sitePath'] . '/plugins/herbie',
    'plugins' => []
];
