<?php

return [
    'cachePath' => 'cache/imagine',
    'collections' => [
        'default' => [
            'filters' => [
                'thumbnail' => [
                    'size' => [360, 240],
                    'mode' => 'outbound',
                ],
            ],
        ],
    ],
];
