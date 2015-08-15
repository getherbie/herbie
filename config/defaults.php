<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'app' => [
        'path' => $APP_PATH
    ],
    'site' => [
        'path' => $SITE_PATH
    ],
    'web' => [
        'path' => $WEB_PATH,
        'url' => $WEB_URL
    ],
    'media' => [
        'path' => $WEB_PATH . '/media',
        'url' => $WEB_URL . '/media',
        'images' => 'jpg,gif,png,svg,ico,tiff,bmp,psd,ai',
        'documents' => 'md,pdf,doc,docx,xls,xlsx,ppt,csv,rtf',
        'archives' => 'zip,tar,gz,gzip,tgz',
        'code' => 'js,css,html,xml,json',
        'videos' => 'mov,avi,ogg,ogv,webm,flv,swf,mp4,mv4',
        'audio' => 'mp3,m4a,wav,aiff,midi'
    ],
    'layouts' => [
        'path' => $SITE_PATH . '/layouts'
    ],
    'theme' => 'default',
    'pages' => [
        'path' => $SITE_PATH . '/pages',
        'extensions' => ['txt', 'md', 'markdown', 'textile', 'htm', 'html', 'rss', 'xml'],
        'extra_paths' => []
    ],
    'posts' => [
        'path' => $SITE_PATH . '/posts',
        'extensions' => ['txt', 'md', 'markdown', 'textile', 'htm', 'html', 'rss', 'xml'],
        'blog_route' => 'blog'
    ],
    'data' => [
        'path' => $SITE_PATH . '/data',
        'extensions' => ['yml', 'yaml']
    ],
    'nice_urls' => false,
    'display_load_time' => false,
    'twig' => [
        'debug' => false,
        'cache' => false, //$SITE_PATH . '/cache/twig',
        'extend' => [
            'functions' => $SITE_PATH . '/twig/functions',
            'filters' => $SITE_PATH . '/twig/filters',
            'tests' => $SITE_PATH . '/twig/tests',
        ],
        'parse_content' => true,
    ],
    'cache' => [
        'page' => [
            'enable' => false,
            'dir' => $SITE_PATH . '/cache/page',
            'expire' => 86400
        ],
        'data' => [
            'enable' => false,
            'dir' => $SITE_PATH . '/cache/data',
            'expire' => 86400
        ]
    ],
    'language' => 'de',
    'locale' => 'de_DE.UTF-8',
    'charset' => 'UTF-8',
    'plugins' => [
        'path' => $SITE_PATH . '/plugins',
        'enable' => [],
        'config' => []
    ]
];
