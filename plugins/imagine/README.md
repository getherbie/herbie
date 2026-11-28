# Imagine System Plugin

`Imagine` is a [Herbie](http://github.com/getherbie) system plugin that integrates [Imagine](https://imagine.readthedocs.io/en/stable/) into your website.

Thanks to Imagine, images can be edited with predefined filters, applying various effects.
Imagine itself is an object-oriented library for image manipulation.
It is based on a well thought design and uses some best practices.

## Installation

The plugin is installed already.

The plugin needs the following PHP package, Herbie throws an exception on start if it is missing:

    composer require imagine/imagine

To activate it, add `imagine` to the `enabledPlugins` configuration option.

~~~php
return [
    'enabledPlugins' => 'imagine'
];
~~~

## Configuration

Under `plugins.imagine` the following options are available:

~~~php
return [
    'plugins' => [
        'imagine' => [
            'cachePath' => 'cache/imagine',
            'collections' => []
        ]
    ]
];
~~~

`cachePath` is a web-relative path below `@web`. The generated image files are stored there
independent of the current working directory, and the generated URLs use the same path.

Examples:

~~~php
// default: files are written to web/cache/imagine and served from /cache/imagine/...
'cachePath' => 'cache/imagine',
~~~

~~~php
// custom web-relative path: files are written to web/media/cache and served from /media/cache/...
'cachePath' => 'media/cache',
~~~

## Filter sets

To use Imagine in Herbie, one or more filter collections must be defined, each containing one or more filters.

The following default collection is always enabled.

~~~php
return [
    // ...
    'collections' => [
        'default' => [
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

In the following configuration example, we see two simple collections for scaling and cropping an image.

~~~php
'imagine'
    'collections' => [
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

With the above configuration you set two Imagine collections `resize` and `crop` that can be applied to images in your project.

- A resize collection to resize an image to a size of 280 x 280 pixels
- A crop collection to crop an image to a size of 560 x 560 pixels

Each collection supports these options:

- `filters` (array): the filters to apply
- `quality` (int, optional): quality value passed to Imagine when saving the image
- `cache` (bool, optional): cache the generated image and reuse it on subsequent requests (default: `true`)

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
        <td>The path to an image within the @media directory. The public URL <code>/media/...</code> is accepted as well.</td>
        <td></td>
    </tr>
    <tr class="param">
        <td>collection</td>
        <td>string</td>
        <td>The filter collection to be applied.</td>
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

### Imagine Twig Function `image_resize()`

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
        <td>The path to an image within the @media directory. The public URL <code>/media/...</code> is accepted as well.</td>
        <td></td>
    </tr>
    <tr class="param">
        <td>collection</td>
        <td>string</td>
        <td>The filter collection to be applied.</td>
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

    {{ image_resize('portrait.jpg', 'crop', {class: 'crop', alt: 'Portrait'}) }}

`imagine()` remains available as a deprecated alias of `image_resize()` and will be removed in 4.0.
The `|imagine` filter keeps its name.

## More Information

For more information, see <https://herbie.tebe.ch>.
