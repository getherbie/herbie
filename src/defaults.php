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
    'media' => [
        'path' => $app['webPath'] . '/media',
        'url' => $app['webUrl'] . '/media',
        'images' => 'jpg,gif,png,svg,ico,tiff,bmp,psd,ai',
        'documents' => 'md,pdf,doc,docx,xls,xlsx,ppt,csv,rtf',
        'archives' => 'zip,tar,gz,gzip,tgz',
        'code' => 'js,css,html,xml,json',
        'videos' => 'mov,avi,ogg,ogv,webm,flv,swf,mp4,mv4',
        'audio' => 'mp3,m4a,wav,aiff,midi'
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
        'blog_route' => 'blog'
    ],
    'data' => [
        'path' => $app['sitePath'] . '/data',
        'extensions' => ['yml', 'yaml']
    ],
    'nice_urls' => false,
    'display_load_time' => false,
    'twig' => [
        'debug' => false,
        'cache' => false, //$app['sitePath'] . '/cache/twig',
        'extend' => [
            'functions' => $app['sitePath'] . '/twig/functions',
            'filters' => $app['sitePath'] . '/twig/filters',
            'tests' => $app['sitePath'] . '/twig/tests',
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
    'plugins' => [
        'path' => $app['sitePath'] . '/plugins',
        'enable' => [],
        'config' => []
    ]
];
