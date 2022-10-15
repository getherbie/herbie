<?php

use herbie\sysplugin\markdown\MarkdownSysPlugin;

return [
    'apiVersion' => 2,
    'pluginName' => 'markdown',
    'pluginClass' => MarkdownSysPlugin::class,
    'pluginPath' => __DIR__,
    'enableTwigFilter' => true,
    'enableTwigFunction' => true
];
