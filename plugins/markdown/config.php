<?php

use herbie\sysplugins\markdown\MarkdownSysPlugin;

return [
    'apiVersion' => 2,
    'pluginName' => 'markdown',
    'pluginClass' => MarkdownSysPlugin::class,
    'pluginPath' => __DIR__,
    'enableTwigFilter' => true,
    'enableTwigFunction' => true,
    // one of: commonmark, gfm, markdown-extra
    // see https://commonmark.thephpleague.com/2.10/extensions/
    'flavor' => 'commonmark',
    // options for the CommonMark environment, they are passed as they are
    // see https://commonmark.thephpleague.com/2.10/configuration/
    'commonmark' => [
        'html_input' => 'allow',
        'allow_unsafe_links' => true,
    ],
];
