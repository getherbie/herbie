---
title: Markdown
layout: doc
---

# Markdown

Pages with the file extension `.md` or `.markdown` are converted to HTML by the markdown system
plugin. The plugin is based on the library
[league/commonmark](https://commonmark.thephpleague.com), which implements the
[CommonMark specification](https://spec.commonmark.org/).

The default flavor `commonmark` implements the specification only. Constructs such as tables,
task lists and footnotes require a different flavor, see [Markdown flavor](#markdown-flavor)
below.

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
        <tr>
            <td><code>markdown-extra</code></td>
            <td>Markdown Extra features on top of CommonMark: attributes, definition lists, footnotes, strikethrough, tables and task lists.</td>
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

## Parser options

Every option of `league/commonmark` can be used in `plugins.markdown.commonmark`. The values are
passed to the parser environment unchanged. A complete reference is available in the
[CommonMark documentation](https://commonmark.thephpleague.com/2.10/configuration/).

Herbie sets `html_input` to `allow` and `allow_unsafe_links` to `true` by default, as shown above.
Further options such as `max_nesting_level`, the `renderer.*` options or the options of the
`gfm` and `markdown-extra` extensions are described in the CommonMark reference.

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

## Supported constructs per flavor

The following table shows which markdown constructs are available in each flavor.

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th>Construct</th>
            <th><code>commonmark</code></th>
            <th><code>gfm</code></th>
            <th><code>markdown-extra</code></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Pipe tables</td>
            <td>plain text</td>
            <td>table</td>
            <td>table</td>
        </tr>
        <tr>
            <td>Task lists</td>
            <td>plain text</td>
            <td>checkbox</td>
            <td>checkbox</td>
        </tr>
        <tr>
            <td>Strikethrough</td>
            <td>plain text</td>
            <td><code>&lt;del&gt;</code></td>
            <td><code>&lt;del&gt;</code></td>
        </tr>
        <tr>
            <td>Footnotes</td>
            <td>link</td>
            <td>link</td>
            <td>footnotes</td>
        </tr>
        <tr>
            <td>Definition lists</td>
            <td>plain text</td>
            <td>plain text</td>
            <td><code>&lt;dl&gt;</code></td>
        </tr>
        <tr>
            <td>Attributes <code>{: #id }</code></td>
            <td>plain text</td>
            <td>plain text</td>
            <td>applied</td>
        </tr>
        <tr>
            <td>Bare URLs</td>
            <td>plain text</td>
            <td>linked</td>
            <td>plain text</td>
        </tr>
        <tr>
            <td>HTML entities</td>
            <td>decoded</td>
            <td>decoded</td>
            <td>decoded</td>
        </tr>
        <tr>
            <td>Raw HTML block</td>
            <td>preserved</td>
            <td>preserved</td>
            <td>preserved</td>
        </tr>
    </tbody>
</table>

Two details are worth pointing out:

- **HTML entities** such as `&copy;` are decoded and output as the character `©`. This rarely
  changes what the visitor sees, but it changes the generated markup.
- **Footnotes** with the `[^1]` syntax are only supported by the flavor `markdown-extra`. With
  the other flavors the caret is treated as the first character of the link target, so `[^1]`
  becomes a link to `1`.

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
