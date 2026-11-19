<?php

return [
    'language' => 'de',
    'locale' => 'de_DE',
    'theme' => 'default',
    'paths' => [
        // Share the themes of the theme-site fixture to test the default theme URL.
        'themes' => 'SITE_PATH/../theme-site/themes',
    ],
    'components' => [
        'twigRenderer' => [
            'debug' => true,
            'cache' => false,
        ],
    ],
    'enabledPlugins' => 'twig',
];
