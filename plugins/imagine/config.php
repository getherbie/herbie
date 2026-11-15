<?php

return [
    'cachePath' => 'cache/imagine',
    'collections' => [
        'default' => [
            'test' => true,
            'filters' => [
                'thumbnail' => [
                    'size' => [360, 240],
                    'mode' => 'outbound',
                ],
            ],
        ],
    ],
];
