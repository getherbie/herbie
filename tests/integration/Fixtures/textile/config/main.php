<?php

return [
    'enabledPlugins' => 'twig,textile',
    'components' => [
        'fileLogger' => null,
    ],
    'plugins' => [
        'textile' => [
            'enableTwigFilter' => false,
            'enableTwigFunction' => false,
        ],
    ],
];
