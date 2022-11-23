# Imagine System Plugin

`Imagine` is a [Herbie](http://github.com/getherbie) system plugin that integrates [Imagine](https://imagine.readthedocs.io/en/stable/) into your website.

Thanks to Imagine, images can be edited with predefined filters, applying various effects.
Imagine itself is an object-oriented library for image manipulation.
It is based on a well thought design and uses some best practices.

## Installation

The plugin is installed already.

To activate it, add `imagine` to the `enabledSysPlugins` configuration option.

~~~php
return [
    'enabledSysPlugins' => 'imagine'
];
~~~

## Configuration

Under `plugins.imagine` the following options are available:

~~~php
return [
    'plugins' => [
        'imagine' => [
            'cachePath' => 'cache/imagine',
            'filterSets' => []
        ]
    ]
];
~~~

## Filter sets

To use Imagine in Herbie, Filter sets must be defined, each containing one or more filters.

The following default filter set is always enabled.

~~~php
return [
    // ...
    'filterSets' => [
        'default' => [
            'test' => true,
            'filters' => [
                'thumbnail' => [
                    'size' => [360, 240],
                    'mode' => 'outbound'
                ]
            ]
        ]
    ]
    // ...
];
~~~

In the following configuration example, we see two simple filter sets for scaling and cropping an image.

~~~php
'imagine'
    'filterSets' => [
        'resize' => [
            'filters' => [
                'thumbnail' => [
                    'size' => [280, 280],
                    'mode' => 'inset',
                ],
            ],
        ],
        'crop' => [
            'filters' => [
                'crop' => [
                    'start' => [0, 0],
                    'size' => [560, 560],
                ],
            ],
        ],        
    ],
],
~~~

With the above configuration you set two Imagine filters `resize` and `crop` that can be applied to images in your project.

- A resize filterSet to resize an image to a size of 280 x 280 pixels
- A crop filterSet to crop an image to a size of 560 x 560 pixels

## Usage

With the activation of the system plugin, one Twig filter and one Twig function are available.

### Imagine Twig Filter

<table>
    <tr class="code">
        <th>Parameter</th>
        <th>Type</th>
        <th>Description</th>
        <th>Default</th>
    </tr>
    <tr class="param">
        <td>path</td>
        <td>string</td>
        <td>The path to an image within the @media directory.</td>
        <td></td>
    </tr>
    <tr class="param">
        <td>filterSet</td>
        <td>string</td>
        <td>The filter set to be applied.</td>
        <td>default</td>
    </tr>
    <tr class="return">
        <td>[return]</td>
        <td>string</td>
        <td colspan="2">The string the web url to the cached image.</td>
    </tr>
</table>

Example:

    <img class="resize" src="{{ 'portrait.jpg'|imagine('resize') }}" alt="Portrait">

### Imagine Twig Function

<table>
    <tr class="code">
        <th>Parameter</th>
        <th>Type</th>
        <th>Description</th>
        <th>Default</th>
    </tr>
    <tr class="param">
        <td>path</td>
        <td>string</td>
        <td>The path to an image within the @media directory.</td>
        <td></td>
    </tr>
    <tr class="param">
        <td>filterSet</td>
        <td>string</td>
        <td>The filter set to be applied.</td>
        <td>default</td>
    </tr>
    <tr class="param">
        <td>attribs</td>
        <td>Array</td>
        <td>Attributes for the generated img HTML element.</td>
        <td>[]</td>
    </tr>
    <tr class="return">
        <td>[return]</td>
        <td>string</td>
        <td colspan="2">The generated img HTML element.</td>
    </tr>
</table>

Example:

    {{ imagine("portrait.jpg", "crop", {class: "crop", alt: "Portrait"}) }}
