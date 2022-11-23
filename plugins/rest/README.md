# Rest System Plugin

`Rest` is a [Herbie](http://github.com/getherbie) system plugin that enables parsing of reStructuredText files.

The plugin itself uses the Composer package `doctrine/rst-parser` under the hood.

## Installation

The plugin is installed already.

To activate it, add `rest` to the `enabledSysPlugins` configuration option.

~~~php
return [
    'enabledSysPlugins' => 'rest'
];
~~~

To enable reStructuredText parsing, the following PHP package must be installed:

- [doctrine/rst-parser](https://packagist.org/packages/doctrine/rst-parser)

To do so, run the following Composer command:

    composer require doctrine/rst-parser

## Configuration

Under `plugins.rest` the following options are available:

~~~php
return [
    'plugins' => [
        'rest' => [
            'enableTwigFilter' => true,
            'enableTwigFunction' => true
        ]
    ]
];
~~~

## Usage

If the plugin is installed and activated properly, content is parsed with the reStructuredText parser.
The prerequisite for this is that `page.format` is equal to `rest`.

In addition, depending on the above configuration, both a Twig filter and a Twig function are available.

The Twig `rest` filter can be applied as follows:

    {{ "My reStructured text formated content"|rest }}

And the Twig `rest` function like this:

    {{ rest("My reStructured text formated content") }}
