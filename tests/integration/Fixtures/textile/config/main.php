<?php

return [
    'enabledSysPlugins' => 'twig,textile',
    'components' => [
        'fileLogger' => null,
    ],
    'plugins' => [
        'textile' => [
            'enableTwigFilter' => false,
            'enableTwigFunction' => false
        ]
    ]
];
