<?php

return [
    'cachePath' => 'cache/imagine',
    'collections' => [
        'default' => [
            'cache' => true,
            'filters' => [
                'thumbnail' => [
                    'size' => [360, 240],
                    'mode' => 'outbound',
                ],
            ],
        ],
    ],
];
