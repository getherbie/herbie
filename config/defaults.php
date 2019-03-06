<?php
/**
 * This file is part of Herbie.
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
        'media' => $SITE_PATH . '/media',
        'pages' => $SITE_PATH . '/pages',
        'plugins' => $SITE_PATH . '/plugins',
        'site' => $SITE_PATH,
        'sysPlugins' => $APP_PATH . '/../sysplugins',
        'themes' => $SITE_PATH . '/themes',
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
        'pages' => ['txt', 'md', 'markdown', 'textile', 'htm', 'html', 'rss', 'xml'],
    ],
    'components' => [
        'downloadMiddleware' => [
            'baseUrl' => '/download/',
            'storagePath' => '@site/media',
        ],
        'urlMatcher' => [
            'rules' => [
                ['blog/author/{author}', 'blog'],
                ['blog/category/{category}', 'blog'],
                ['blog/tag/{tag}', 'blog'],
                ['blog/{year}/{month}/{day}', 'blog', ['year' => '[0-9]{4}', 'month' => '[0-9]{2}', 'day' => '[0-9]{2}']],
                ['blog/{year}/{month}', 'blog', ['year' => '[0-9]{4}', 'month' => '[0-9]{2}']],
                ['blog/{year}', 'blog', ['year' => '[0-9]{4}']]
            ]
        ]
    ],
    'twig' => [
        'debug' => false,
        'cache' => false,
        'functionsPath' => $SITE_PATH . '/twig/functions',
        'filtersPath' => $SITE_PATH . '/twig/filters',
        'testsPath' => $SITE_PATH . '/twig/tests'
    ],
    'plugins' => [],
    'enabledPlugins' => [],
    'enabledSysPlugins' => ['adminpanel']
];
