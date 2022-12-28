<?php

declare(strict_types=1);

use herbie\sysplugins\dummy\DummySysPlugin;

return [
    'apiVersion' => 2,
    'pluginName' => 'dummy',
    'pluginClass' => DummySysPlugin::class,
    'pluginPath' => __DIR__,
];
