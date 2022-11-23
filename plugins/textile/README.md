# Textile System Plugin

`Textile` is a [Herbie](http://github.com/getherbie) system plugin that enables parsing of Textile files.

The plugin itself uses the Composer package `doctrine/rst-parser` under the hood.

## Installation

The plugin is installed already.

To activate it, add `textile` to the `enabledSysPlugins` configuration option.

~~~php
return [
    'enabledSysPlugins' => 'textile'
];
~~~

To enable Textile parsing, the following PHP package must be installed:

- [netcarver/textile](https://packagist.org/packages/netcarver/textile)

To do so, run the following Composer command:

    composer require netcarver/textile

## Configuration

Under `plugins.textile` the following options are available:

~~~php
return [
    'plugins' => [
        'textile' => [
            'enableTwigFilter' => true,
            'enableTwigFunction' => true
        ]
    ]
];
~~~

## Usage

If the plugin is installed and activated properly, content is parsed with the Textile parser.
The prerequisite for this is that `page.format` is equal to `textile`.

In addition, depending on the above configuration, both a Twig filter and a Twig function are available.

The Twig `textile` filter can be applied as follows:

    {{ "My Textile formated content"|textile }}

And the Twig `textile` function like this:

    {{ textile("My Textile formated content") }}

## More Information

For more information, see <https://herbie.tebe.ch>.
