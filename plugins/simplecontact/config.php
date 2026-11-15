<?php

declare(strict_types=1);

use herbie\sysplugins\simplecontact\SimplecontactSysPlugin;

return [
    'apiVersion' => 2,
    'pluginName' => 'simplecontact',
    'pluginClass' => SimplecontactSysPlugin::class,
    'pluginPath' => __DIR__,
    'config' => [
        'recipient' => 'me@example.com', // your email address
        'template' => null, // aliased path to custom template
    ],
];
