<?php

return [
    'language' => 'de',
    'locale' => 'de_DE',
    'theme' => 'default',
    'niceUrls' => true,
    'fileExtensions' => [
        'layouts' => 'twig'
    ],
    'twig' => [
        'debug' => true
    ],
    'enabledPlugins' => '',
    'enabledSysPlugins' => 'twig_core,twig_plus,dummy,imagine,markdown,rest,textile',
    'plugins' => [
        'imagine' => [
            'filterSets' => [
                'bsp1' => [
                    'filters' => [
                        'thumbnail' => [
                            'size' => [220, 220],
                            'mode' => 'inset',
                        ],
                    ],
                ],
                'bsp2' => [
                    'filters' => [
                        'crop' => [
                            'start' => [130, 250],
                            'size' => [520, 390],
                        ],
                        'thumbnail' => [
                            'size' => [220, 220],
                            'mode' => 'inset',
                        ],
                    ],
                ],
                'bsp3' => [
                    'filters' => [
                        'grayscale' => null,
                        'thumbnail' => [
                            'size' => [220, 220],
                            'mode' => 'inset',
                        ],
                    ],
                ],
                'bsp4' => [
                    'filters' => [
                        'colorize' => [
                            'color' => '#ff0000',
                        ],
                        'thumbnail' => [
                            'size' => [220, 220],
                            'mode' => 'inset',
                        ],
                    ],
                ],
                'bsp5' => [
                    'filters' => [
                        'negative' => null,
                        'thumbnail' => [
                            'size' => [220, 220],
                            'mode' => 'inset',
                        ],
                    ],
                ],
                'bsp6' => [
                    'filters' => [
                        'sharpen' => null,
                        'thumbnail' => [
                            'size' => [220, 220],
                            'mode' => 'inset',
                        ],
                    ],
                ],
                'bsp7' => [
                    'filters' => [
                        'gamma' => [
                            'correction' => 0.3,
                        ],
                        'thumbnail' => [
                            'size' => [220, 220],
                            'mode' => 'inset',
                        ],
                    ],
                ],
                'bsp8' => [
                    'filters' => [
                        'rotate' => [
                            'angle' => 90,
                        ],
                        'thumbnail' => [
                            'size' => [220, 165],
                            'mode' => 'outbound',
                        ],
                    ],
                ],
                'bsp9' => [
                    'filters' => [
                        'flipVertically' => null,
                        'thumbnail' => [
                            'size' => [220, 220],
                            'mode' => 'inset',
                        ],
                    ],
                ],
                'bsp10' => [
                    'filters' => [
                        'flipHorizontally' => null,
                        'thumbnail' => [
                            'size' => [220, 220],
                            'mode' => 'inset',
                        ],
                    ],
                ],
                'bsp11' => [
                    'filters' => [
                        'resize' => [
                            'size' => [220, 165],
                        ],
                    ],
                ],
                'bsp12' => [
                    'filters' => [
                        'thumbnail' => [
                            'size' => [10, 10],
                            'mode' => 'inset',
                        ],
                        'upscale' => [
                            'min' => [165, 165],
                        ],
                    ],
                ],
                'bsp13' => [
                    'filters' => [
                        'relativeResize' => [
                            'method' => 'widen',
                            'parameter' => 200,
                        ],
                    ],
                ],
                'bsp14' => [
                    'filters' => [
                        'relativeResize' => [
                            'method' => 'heighten',
                            'parameter' => 150,
                        ],
                    ],
                ],
                'bsp15' => [
                    'filters' => [
                        'thumbnail' => [
                            'size' => [20, 20],
                            'mode' => 'inset',
                        ],
                        'relativeResize' => [
                            'method' => 'scale',
                            'parameter' => 10,
                        ],
                    ],
                ],
                'bsp16' => [
                    'filters' => [
                        'thumbnail' => [
                            'size' => [20, 20],
                            'mode' => 'inset',
                        ],
                        'relativeResize' => [
                            'method' => 'increase',
                            'parameter' => 135,
                        ],
                    ],
                ],
            ],
        ],
    ],
];
