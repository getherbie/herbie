<?php

declare(strict_types=1);

use herbie\sysplugins\adminpanel\AdminpanelPlugin;

return [
    'apiVersion' => 2,
    'pluginName' => 'adminpanel',
    'pluginClass' => AdminpanelPlugin::class,
    'pluginPath' => __DIR__,
];
