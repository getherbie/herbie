<?php

return [
    'language' => 'de',
    'locale' => 'de_DE.UTF-8',
    'charset' => 'UTF-8',
    'theme' => 'default',
    'niceUrls' => false,
    'paths' => [
        'app' => 'APP_PATH',
        'data' => 'SITE_PATH/data',
        'media' => 'SITE_PATH/media',
        'pages' => 'SITE_PATH/pages',
        'plugins' => 'SITE_PATH/extend/plugins',
        'site' => 'SITE_PATH',
        'themes' => 'SITE_PATH/themes',
        'web' => 'WEB_PATH'
    ],
    'urls' => [
        'media' => 'WEB_URL/media',
        'web' => 'WEB_URL/',
    ],
    'fileExtensions' => [
        'layouts' => 'twig',
        'media' => [
            'images' => 'ai,bmp,gif,ico,jpg,png,psd,svg,tiff',
            'documents' => 'csv,doc,docx,md,pdf,ppt,rtf,xls,xlsx',
            'archives' => 'gz,gzip,tar,tgz,zip',
            'code' => 'css,html,js,json,xml',
            'videos' => 'avi,flv,mov,mp4,mv4,ogg,ogv,swf,webm',
            'audio' => 'aiff,m4a,midi,mp3,wav'
        ],
        'pages' => 'htm,html,markdown,md,rss,rst,textile,txt,xml',
    ],
    'components' => [
        'dataRepository' => [
            'adapter' => 'json'
        ],
        'downloadMiddleware' => [
            'baseUrl' => '/download/',
            'storagePath' => '@site/media',
        ],
        'urlMatcher' => [
            'rules' => []
        ]
    ],
    'twig' => [
        'autoescape' => 'html',
        'cache' => false,
        'charset' => 'UTF-8',
        'debug' => false,
        'filtersPath' => 'SITE_PATH/extend/twig/filters',
        'functionsPath' => 'SITE_PATH/extend/twig/functions',
        'strictVariables' => false,
        'testsPath' => 'SITE_PATH/extend/twig/tests'
    ],
    'plugins' => [],
    'enabledPlugins' => '',
    'enabledSysPlugins' => 'imagine'
];
