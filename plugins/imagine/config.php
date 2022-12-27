<?php

use herbie\sysplugin\imagine\ImagineSysPlugin;

return [
    'apiVersion' => 2,
    'pluginName' => 'imagine',
    'pluginClass' => ImagineSysPlugin::class,
    'pluginPath' => __DIR__,
    'cachePath' => 'cache/imagine',
    'collections' => [
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
