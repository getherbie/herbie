<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'language' => 'de',
    'locale' => 'de_DE.UTF-8',
    'charset' => 'UTF-8',
    'theme' => 'default',
    'niceUrls' => false,
    'paths' => [
        'app' => $APP_PATH,
        'data' => $SITE_PATH . '/data',
        'messages' => $APP_PATH . '/../messages',
        'layouts' => $SITE_PATH . '/layouts',
        'media' => $WEB_PATH . '/media',
        'pages' => $SITE_PATH . '/pages',
        'plugins' => $SITE_PATH . '/plugins',
        'site' => $SITE_PATH,
        'twig' => [
            'cache' => $SITE_PATH . '/cache/twig',
            'functions' => $SITE_PATH . '/twig/functions',
            'filters' => $SITE_PATH . '/twig/filters',
            'tests' => $SITE_PATH . '/twig/tests',
        ],
        'web' => $WEB_PATH
    ],
    'urls' => [
        'media' => $WEB_URL . '/media',
        'web' => $WEB_URL . '/',
    ],
    'fileExtensions' => [
        'data' => ['yml', 'yaml'],
        'layouts' => 'html',
        'media' => [
            'images' => 'jpg,gif,png,svg,ico,tiff,bmp,psd,ai',
            'documents' => 'md,pdf,doc,docx,xls,xlsx,ppt,csv,rtf',
            'archives' => 'zip,tar,gz,gzip,tgz',
            'code' => 'js,css,html,xml,json',
            'videos' => 'mov,avi,ogg,ogv,webm,flv,swf,mp4,mv4',
            'audio' => 'mp3,m4a,wav,aiff,midi'
        ],
        'pages' => ['txt','md','markdown','textile','htm','html','rss','xml'],
    ],
    'twig' => [
        'debug' => false,
        'cache' => false,
    ],
    'plugins' => [],
    'enabledPlugins' => [],
    'enabledSysPlugins' => ['markdown', 'textile']
];