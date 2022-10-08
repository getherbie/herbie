<?php

return [
    'apiVersion' => 2,
    'pluginName' => 'imagine',
    'pluginClass' => __DIR__ . '/ImagineSysPlugin.php',
    'pluginPath' => __DIR__,
    'cachePath' => 'cache/imagine',
    'filterSets' => [
        'default' => [
            'test' => true,
            'filters' => [
                'thumbnail' => [
                    'size' => [360, 240],
                    'mode' => 'outbound'
                ]
            ]
        ]
    ]
];
