---
title: Markdown
layout: doc
---

# Markdown

Pages with the file extension `.md` or `.markdown` are converted to HTML by the markdown system
plugin. The plugin is based on the [league/commonmark](https://commonmark.thephpleague.com) library,
a PHP Markdown parser that supports CommonMark, GitHub Flavored Markdown, and additional
extensions.

The default flavor `commonmark` implements the specification only. Constructs such as tables
and task lists require `gfm`, see [Markdown flavor](doc/contents/markdown#markdown-flavor) below.

## Configuration

The plugin is configured under `plugins.markdown` in the configuration file `site/config/main.php`.
These are the defaults:

~~~php
return [
    'plugins' => [
        'markdown' => [
            'enableTwigFilter' => true,
            'enableTwigFunction' => true,
            'flavor' => 'commonmark',
            'extensions' => [],
            'commonmark' => [
                'html_input' => 'allow',
                'allow_unsafe_links' => true,
            ],
        ],
    ],
];
~~~

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th>Option</th>
            <th>Default</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>enableTwigFilter</code></td>
            <td><code>true</code></td>
            <td>Registers the Twig filter <code>markdown</code></td>
        </tr>
        <tr>
            <td><code>enableTwigFunction</code></td>
            <td><code>true</code></td>
            <td>Registers the Twig function <code>markdown()</code></td>
        </tr>
        <tr>
            <td><code>flavor</code></td>
            <td><code>commonmark</code></td>
            <td>The markdown dialect, see below</td>
        </tr>
        <tr>
            <td><code>extensions</code></td>
            <td><code>[]</code></td>
            <td>Additional CommonMark extension classes on top of the selected flavor</td>
        </tr>
        <tr>
            <td><code>commonmark</code></td>
            <td>see above</td>
            <td>Options for the CommonMark environment</td>
        </tr>
    </tbody>
</table>

## Markdown flavor

The option `flavor` selects the set of CommonMark extensions. An unknown value falls back to
`commonmark` without an error message.

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th>Flavor</th>
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>commonmark</code></td>
            <td>The <a href="https://spec.commonmark.org/">CommonMark specification</a>. This is the default.</td>
        </tr>
        <tr>
            <td><code>gfm</code></td>
            <td><a href="https://github.github.com/gfm/">GitHub Flavored Markdown</a>: adds autolinks, tables, strikethrough, task lists and filtered raw HTML.</td>
        </tr>
    </tbody>
</table>

To use GitHub Flavored Markdown, for example so that tables and task lists work out of the box:

~~~php
return [
    'plugins' => [
        'markdown' => [
            'flavor' => 'gfm',
        ],
    ],
];
~~~

## Supported constructs per flavor

The following table shows which markdown constructs are available in each flavor.

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th>Construct</th>
            <th><code>commonmark</code></th>
            <th><code>gfm</code></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Pipe tables</td>
            <td>plain text</td>
            <td>table</td>
        </tr>
        <tr>
            <td>Task lists</td>
            <td>plain text</td>
            <td>checkbox</td>
        </tr>
        <tr>
            <td>Strikethrough</td>
            <td>plain text</td>
            <td><code>&lt;del&gt;</code></td>
        </tr>
        <tr>
            <td>Bare URLs</td>
            <td>plain text</td>
            <td>linked</td>
        </tr>
        <tr>
            <td>HTML entities</td>
            <td>decoded</td>
            <td>decoded</td>
        </tr>
        <tr>
            <td>Raw HTML block</td>
            <td>preserved</td>
            <td>preserved</td>
        </tr>
    </tbody>
</table>

One detail is worth pointing out:

- **HTML entities** such as `&copy;` are decoded and output as the character `©`. This rarely
  changes what the visitor sees, but it changes the generated markup.

## Additional extensions

The option `extensions` enables additional extension classes on top of the selected flavor. Many
extensions shipped with `league/commonmark` can be used without an additional Composer package.
Each class must implement `League\CommonMark\Extension\ExtensionInterface` and be instantiable
without constructor arguments; duplicates are ignored. Extensions that require custom
programmatic setup cannot be added through configuration alone.

To enable typographic quotes, dashes and ellipses:

~~~php
return [
    'plugins' => [
        'markdown' => [
            'extensions' => [
                \League\CommonMark\Extension\SmartPunct\SmartPunctExtension::class,
            ],
            'commonmark' => [
                'smartpunct' => [
                    'double_quote_opener' => '“',
                    'double_quote_closer' => '”',
                    'single_quote_opener' => '‘',
                    'single_quote_closer' => '’',
                ],
            ],
        ],
    ],
];
~~~

If an extension provides configuration, pass it through `plugins.markdown.commonmark` as usual.

## Twig and Markdown processing order

By default, Herbie processes page segments in this order:

1. Twig renders the segment when `page.twig` is enabled and `plugins.CORE.enableTwigInSegmentFilter` is `true`.
2. The Markdown plugin parses the resulting segment for Markdown pages.

This means Twig output becomes part of the Markdown input. HTML returned by Twig functions can therefore break when placed inside Markdown syntax. For links inside Markdown, prefer URL-only helpers such as `url_rel()` or `url_abs()`:

~~~markdown
[Variables]({{ '{{' }} url_rel('doc/contents/variables') }})
~~~

Because segment rendering is event-based, custom listeners or priorities can change this behavior.

## Parser options

Every option of `league/commonmark` can be used in `plugins.markdown.commonmark`. The values are
passed to the parser environment unchanged. A complete reference is available in the
[configuration reference](https://commonmark.thephpleague.com/2.x/configuration/).

Herbie sets `html_input` to `allow` and `allow_unsafe_links` to `true` by default, as shown above.
Further options such as `max_nesting_level`, the `renderer.*` options or the `gfm` extension
options are described in the configuration reference.

Please note that a misspelled **option name** is silently ignored, while an invalid **value**
throws an exception.

### Restricting raw HTML

Raw HTML in markdown pages is rendered as it is by default. If the content comes from untrusted
authors, this is a security risk. The option `html_input` allows to restrict it:

~~~php
return [
    'plugins' => [
        'markdown' => [
            'commonmark' => [
                'html_input' => 'escape',
                'allow_unsafe_links' => false,
            ],
        ],
    ],
];
~~~

### Raw HTML blocks

Raw HTML blocks end at the first empty line according to the CommonMark specification. Herbie
protects such blocks before parsing, so a block spread over several paragraphs still renders as
HTML:

~~~html
<div>
Paragraph 1

Paragraph 2
</div>
~~~

Indented code samples that contain HTML remain code blocks.

## Twig filter and function

In addition to page content, the parser is available in Twig templates as filter and as function.

~~~twig
{{ '{{' }} '# A heading'|markdown {{ '}}' }}
~~~

~~~twig
{{ '{{' }} markdown('# A heading') {{ '}}' }}
~~~

Both are registered by default and can be disabled with `enableTwigFilter` and
`enableTwigFunction`.
