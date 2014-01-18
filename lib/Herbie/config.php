<?php

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
        'path' => $this['sitePath'] . '/pages'
    ],
    'posts' => [
        'path' => $this['sitePath'] . '/posts'
    ],
    'data' => [
        'path' => $this['sitePath'] . '/data',
        'extensions' => ['yml', 'yaml']
    ],
    'nice_urls' => true,
    'twig' => [
        'debug' => false,
        'cache' => $this['sitePath'] . '/cache/twig',
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
        'from' => '<box>|</box>|<box:info></box:info>',
        'to'   => '<div class="box" markdown="1">|</div>|<div class="box box-info" markdown="1">|</div>',
    ]
];