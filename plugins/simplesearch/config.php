<?php

declare(strict_types=1);

use herbie\sysplugins\simplesearch\SimplesearchSysPlugin;

return [
    'apiVersion' => 2,
    'pluginName' => 'simplesearch',
    'pluginClass' => SimplesearchSysPlugin::class,
    'pluginPath' => __DIR__,
    'config' => [
        'formTemplate' => null,
        'resultsTemplate' => null,
        'usePageCache' => false,
    ],
];
