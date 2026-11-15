# Markdown System Plugin

`Markdown` is a [Herbie](http://github.com/getherbie) system plugin that enables parsing of Markdown files.

The plugin uses the package `league/commonmark`, a PHP parser for the [CommonMark specification](https://commonmark.org/).

## Installation

The plugin is installed already.

To activate it, add `markdown` to the `enabledPlugins` configuration option.

~~~php
return [
    'enabledPlugins' => 'markdown'
];
~~~

To enable Markdown parsing, the following parser must be installed:

    composer require league/commonmark

## Configuration

The plugin is configured under `plugins.markdown`. These are the defaults:

~~~php
return [
    'plugins' => [
        'markdown' => [
            'enableTwigFilter' => true,
            'enableTwigFunction' => true,
            'flavor' => 'commonmark',
            'commonmark' => [
                'html_input' => 'allow',
                'allow_unsafe_links' => true,
            ],
        ]
    ]
];
~~~

Every value can be overridden. In addition to `html_input` and `allow_unsafe_links` shown
above, all further options of `league/commonmark` are available, they are listed in the
tables below.

### Markdown flavour

The option `flavor` selects the set of CommonMark extensions:

| Flavor | Description |
| --- | --- |
| `commonmark` | The [CommonMark specification](https://spec.commonmark.org/). This is the default. |
| `gfm` | [GitHub Flavored Markdown](https://github.github.com/gfm/): adds autolinks, tables, strikethrough, task lists and filtered raw HTML. |
| `markdown-extra` | Markdown Extra features on top of CommonMark: attributes, definition lists, footnotes, strikethrough, tables and task lists. |

An unknown value falls back to `commonmark`.

### CommonMark options

Every option of `league/commonmark` can be used in `plugins.markdown.commonmark`, they are
passed to the parser environment as they are. A complete reference is available at
<https://commonmark.thephpleague.com/2.10/configuration/>.

Please note that a misspelled **option name** is silently ignored, while an invalid **value**
throws an exception.

#### Available for all flavours

| Option | Default | Description |
| --- | --- | --- |
| `html_input` | `allow` | `allow` renders raw HTML, `escape` shows it as text, `strip` removes it |
| `allow_unsafe_links` | `true` | If `false`, links like `javascript:` or `data:` are removed |
| `max_nesting_level` | `PHP_INT_MAX` | Maximum depth of nested elements |
| `max_delimiters_per_line` | `PHP_INT_MAX` | Maximum number of inline delimiters per line |
| `renderer.block_separator` | `"\n"` | Separator between rendered blocks |
| `renderer.inner_separator` | `"\n"` | Separator between inner elements |
| `renderer.soft_break` | `"\n"` | Replacement for a soft line break |
| `slug_normalizer.instance` | `SlugNormalizer` | Normalizer for heading ids |
| `slug_normalizer.max_length` | `255` | Maximum length of a heading id |
| `slug_normalizer.unique` | `document` | `disabled`, `document` or `environment` |
| `slug_normalizer.reserved` | `[]` | Reserved heading ids |
| `xml.max_indentation_level` | `16` | Indentation of the XML renderer |

#### Additional options for the flavour `gfm`

| Option | Default | Description |
| --- | --- | --- |
| `autolink.allowed_protocols` | `["http", "https", "ftp"]` | Protocols that are turned into links |
| `autolink.default_protocol` | `http` | Protocol for URLs without one, e.g. `www.example.com` |
| `table.wrap.enabled` | `false` | Wraps every table in an element |
| `table.wrap.tag` | `div` | Tag of the wrapper |
| `table.wrap.attributes` | `[]` | Attributes of the wrapper |
| `table.alignment_attributes.left` | `["align" => "left"]` | Attributes for left aligned cells |
| `table.alignment_attributes.center` | `["align" => "center"]` | Attributes for centered cells |
| `table.alignment_attributes.right` | `["align" => "right"]` | Attributes for right aligned cells |
| `table.max_autocompleted_cells` | `10000` | Limit for auto-completed cells of a table |
| `disallowed_raw_html.disallowed_tags` | `title`, `textarea`, `style`, `xmp`, `iframe`, `noembed`, `noframes`, `script`, `plaintext` | These HTML tags are escaped |

#### Additional options for the flavour `markdown-extra`

| Option | Default | Description |
| --- | --- | --- |
| `table.*` | – | The same table options as in `gfm` |
| `attributes.allow` | `[]` | Attribute names that are allowed, e.g. `['id', 'class']` |
| `footnote.backref_class` | `footnote-backref` | CSS class of the back reference |
| `footnote.backref_symbol` | `↩` | Symbol of the back reference |
| `footnote.container_add_hr` | `true` | Adds a horizontal rule above the footnotes |
| `footnote.container_class` | `footnotes` | CSS class of the footnote container |
| `footnote.enable_inline_footnotes` | `true` | Allows inline footnotes |
| `footnote.ref_class` | `footnote-ref` | CSS class of the footnote reference |
| `footnote.ref_id_prefix` | `fnref:` | Prefix of the reference id |
| `footnote.footnote_class` | `footnote` | CSS class of the footnote |
| `footnote.footnote_id_prefix` | `fn:` | Prefix of the footnote id |

## Usage

If the plugin is installed and activated properly, content is parsed with the CommonMark parser.
The prerequisite for this is that `page.format` is equal to `markdown`.

In addition, both a Twig filter and a Twig "Markdown" function are available.

The Twig filter can be applied as follows:

    {{ "# Title"|markdown }}

And the Twig function like this:

    {{ markdown("# Title") }}

## More Information

For more information, see <https://herbie.tebe.ch>.
