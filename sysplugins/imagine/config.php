<?php

return [
    'cachePath' => 'cache/imagine',
    'filterSets' => [
        'default' => [
            'test' => true,
            'filters' => [
                'thumbnail' => [
                    'size' => [
                        360,
                        240
                    ],
                    'mode' => 'outbound'
                ]
            ]
        ]
    ]
];
