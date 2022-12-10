# Markdown System Plugin

`Markdown` is a [Herbie](http://github.com/getherbie) system plugin that enables parsing of Markdown files.

The plugins itself useses the packages `erusev/parsedown` or `erusev/parsedown-extra`.

## Installation

The plugin is installed already.

To activate it, add `markdown` to the `enabledSysPlugins` configuration option.

~~~php
return [
    'enabledSysPlugins' => 'markdown'
];
~~~

To enable Markdow parsing, one of the following parsers must be installed:

- erusev/parsedown
- erusev/parsedown-extra

To do so, run the following composer command:

    composer require erusev/parsedown

Or, if you want some extra Markdown features:

    composer require erusev/parsedown-extra

## Configuration

Under `plugins.markdown` the following options are available:

~~~php
return [
    'plugins' => [
        'markdown' => [
            'enableTwigFilter' => true,
            'enableTwigFunction' => true
        ]
    ]
];
~~~

## Usage

If the plugin is installed and activated properly, content is parsed with one of the available parsers.
The prerequisite for this is that `page.format` is equal to `markdown`.

In addition, both a Twig filter and a Twig "Markdown" function are available.

The Twig filter can be applied as follows:

    {{ "# Title"|h_markdown }}

And the Twig function like this:

    {{ h_markdown("# Title") }}

## More Information

For more information, see <https://herbie.tebe.ch>.
