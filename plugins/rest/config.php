<?php

use herbie\sysplugins\rest\RestSysPlugin;

return [
    'apiVersion' => 2,
    'pluginName' => 'rest',
    'pluginClass' => RestSysPlugin::class,
    'pluginPath' => __DIR__,
    'enableTwigFilter' => true,
    'enableTwigFunction' => true
];
