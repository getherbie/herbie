<?php

use herbie\sysplugins\textile\TextileSysPlugin;

return [
    'apiVersion' => 2,
    'pluginName' => 'textile',
    'pluginClass' => TextileSysPlugin::class,
    'pluginPath' => __DIR__,
    'enableTwigFilter' => true,
    'enableTwigFunction' => true
];
