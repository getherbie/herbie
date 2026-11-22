<?php

return [
    'enableTwigFilter' => true,
    'enableTwigFunction' => true,
    // one of: commonmark, gfm
    'flavor' => 'commonmark',
    // additional extension classes, added to the selected flavor
    // see https://commonmark.thephpleague.com/2.x/extensions/
    'extensions' => [],
    // options for the CommonMark environment, they are passed as they are
    // see https://commonmark.thephpleague.com/2.x/configuration/
    'commonmark' => [
        'html_input' => 'allow',
        'allow_unsafe_links' => true,
    ],
];
