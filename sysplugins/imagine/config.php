<?php

return [
    'apiVersion' => 2,
    'pluginName' => 'imagine',
    'pluginPath' => __DIR__,
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
