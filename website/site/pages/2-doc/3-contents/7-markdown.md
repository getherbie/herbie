---
title: Markdown
layout: doc
---

# Markdown

Pages with the file extension `.md` or `.markdown` are converted to HTML by the markdown system
plugin. The plugin is based on the library
[league/commonmark](https://commonmark.thephpleague.com), which implements the
[CommonMark specification](https://spec.commonmark.org/).

Please note that the plugin used Parsedown in Herbie 2.x. The switch to `league/commonmark` is a
**breaking change**: some markdown constructs are rendered differently, and a few Parsedown
extensions are not available any more. See [Differences to Parsedown](#differences-to-parsedown)
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

## Markdown flavour

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
            <td>The [CommonMark specification](https://spec.commonmark.org/). This is the default.</td>
        </tr>
        <tr>
            <td><code>gfm</code></td>
            <td>[GitHub Flavored Markdown](https://github.github.com/gfm/): adds autolinks, tables, strikethrough, task lists and filtered raw HTML.</td>
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
passed to the parser environment unchanged. A complete reference is available at
<https://commonmark.thephpleague.com/2.10/configuration/>.

Please note that a misspelled **option name** is silently ignored, while an invalid **value**
throws an exception.

### Available for all flavours

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
            <td><code>html_input</code></td>
            <td><code>allow</code></td>
            <td><code>allow</code> renders raw HTML, <code>escape</code> shows it as text, <code>strip</code> removes it</td>
        </tr>
        <tr>
            <td><code>allow_unsafe_links</code></td>
            <td><code>true</code></td>
            <td>If <code>false</code>, links like <code>javascript:</code> or <code>data:</code> are removed</td>
        </tr>
        <tr>
            <td><code>max_nesting_level</code></td>
            <td><code>PHP_INT_MAX</code></td>
            <td>Maximum depth of nested elements</td>
        </tr>
        <tr>
            <td><code>max_delimiters_per_line</code></td>
            <td><code>PHP_INT_MAX</code></td>
            <td>Maximum number of inline delimiters per line</td>
        </tr>
        <tr>
            <td><code>renderer.block_separator</code></td>
            <td><code>"\n"</code></td>
            <td>Separator between rendered blocks</td>
        </tr>
        <tr>
            <td><code>renderer.inner_separator</code></td>
            <td><code>"\n"</code></td>
            <td>Separator between inner elements</td>
        </tr>
        <tr>
            <td><code>renderer.soft_break</code></td>
            <td><code>"\n"</code></td>
            <td>Replacement for a soft line break</td>
        </tr>
        <tr>
            <td><code>slug_normalizer.instance</code></td>
            <td><code>SlugNormalizer</code></td>
            <td>Normalizer for heading ids</td>
        </tr>
        <tr>
            <td><code>slug_normalizer.max_length</code></td>
            <td><code>255</code></td>
            <td>Maximum length of a heading id</td>
        </tr>
        <tr>
            <td><code>slug_normalizer.unique</code></td>
            <td><code>document</code></td>
            <td><code>disabled</code>, <code>document</code> or <code>environment</code></td>
        </tr>
        <tr>
            <td><code>slug_normalizer.reserved</code></td>
            <td><code>[]</code></td>
            <td>Reserved heading ids</td>
        </tr>
        <tr>
            <td><code>xml.max_indentation_level</code></td>
            <td><code>16</code></td>
            <td>Indentation of the XML renderer</td>
        </tr>
    </tbody>
</table>

### Additional options for the flavour `gfm`

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
            <td><code>autolink.allowed_protocols</code></td>
            <td><code>["http", "https", "ftp"]</code></td>
            <td>Protocols that are turned into links</td>
        </tr>
        <tr>
            <td><code>autolink.default_protocol</code></td>
            <td><code>http</code></td>
            <td>Protocol for URLs without one, e.g. <code>www.example.com</code></td>
        </tr>
        <tr>
            <td><code>table.wrap.enabled</code></td>
            <td><code>false</code></td>
            <td>Wraps every table in an element</td>
        </tr>
        <tr>
            <td><code>table.wrap.tag</code></td>
            <td><code>div</code></td>
            <td>Tag of the wrapper</td>
        </tr>
        <tr>
            <td><code>table.wrap.attributes</code></td>
            <td><code>[]</code></td>
            <td>Attributes of the wrapper</td>
        </tr>
        <tr>
            <td><code>table.alignment_attributes.left</code></td>
            <td><code>["align" =&gt; "left"]</code></td>
            <td>Attributes for left aligned cells</td>
        </tr>
        <tr>
            <td><code>table.alignment_attributes.center</code></td>
            <td><code>["align" =&gt; "center"]</code></td>
            <td>Attributes for centered cells</td>
        </tr>
        <tr>
            <td><code>table.alignment_attributes.right</code></td>
            <td><code>["align" =&gt; "right"]</code></td>
            <td>Attributes for right aligned cells</td>
        </tr>
        <tr>
            <td><code>table.max_autocompleted_cells</code></td>
            <td><code>10000</code></td>
            <td>Limit for auto-completed cells of a table</td>
        </tr>
        <tr>
            <td><code>disallowed_raw_html.disallowed_tags</code></td>
            <td><code>title</code>, <code>textarea</code>, <code>style</code>, <code>xmp</code>, <code>iframe</code>, <code>noembed</code>, <code>noframes</code>, <code>script</code>, <code>plaintext</code></td>
            <td>These HTML tags are escaped</td>
        </tr>
    </tbody>
</table>

### Additional options for the flavour `markdown-extra`

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
            <td><code>table.*</code></td>
            <td>–</td>
            <td>The same table options as in <code>gfm</code></td>
        </tr>
        <tr>
            <td><code>attributes.allow</code></td>
            <td><code>[]</code></td>
            <td>Attribute names that are allowed, e.g. <code>['id', 'class']</code></td>
        </tr>
        <tr>
            <td><code>footnote.backref_class</code></td>
            <td><code>footnote-backref</code></td>
            <td>CSS class of the back reference</td>
        </tr>
        <tr>
            <td><code>footnote.backref_symbol</code></td>
            <td><code>↩</code></td>
            <td>Symbol of the back reference</td>
        </tr>
        <tr>
            <td><code>footnote.container_add_hr</code></td>
            <td><code>true</code></td>
            <td>Adds a horizontal rule above the footnotes</td>
        </tr>
        <tr>
            <td><code>footnote.container_class</code></td>
            <td><code>footnotes</code></td>
            <td>CSS class of the footnote container</td>
        </tr>
        <tr>
            <td><code>footnote.enable_inline_footnotes</code></td>
            <td><code>true</code></td>
            <td>Allows inline footnotes</td>
        </tr>
        <tr>
            <td><code>footnote.ref_class</code></td>
            <td><code>footnote-ref</code></td>
            <td>CSS class of the footnote reference</td>
        </tr>
        <tr>
            <td><code>footnote.ref_id_prefix</code></td>
            <td><code>fnref:</code></td>
            <td>Prefix of the reference id</td>
        </tr>
        <tr>
            <td><code>footnote.footnote_class</code></td>
            <td><code>footnote</code></td>
            <td>CSS class of the footnote</td>
        </tr>
        <tr>
            <td><code>footnote.footnote_id_prefix</code></td>
            <td><code>fn:</code></td>
            <td>Prefix of the footnote id</td>
        </tr>
    </tbody>
</table>

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

## Differences to Parsedown

The following constructs behave differently or are no longer supported. The list is not
exhaustive, it names the cases that are known to differ. The rendering was compared against
Herbie 2.x for each case.

<table class="pure-table pure-table-horizontal">
    <thead>
        <tr>
            <th>Construct</th>
            <th>Herbie 2.x (Parsedown Extra)</th>
            <th><code>commonmark</code></th>
            <th><code>gfm</code></th>
            <th><code>markdown-extra</code></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Pipe tables</td>
            <td>table</td>
            <td>plain text</td>
            <td>table</td>
            <td>table</td>
        </tr>
        <tr>
            <td>Task lists</td>
            <td>plain text</td>
            <td>plain text</td>
            <td>checkbox</td>
            <td>checkbox</td>
        </tr>
        <tr>
            <td>Strikethrough</td>
            <td><code>&lt;del&gt;</code></td>
            <td>plain text</td>
            <td><code>&lt;del&gt;</code></td>
            <td><code>&lt;del&gt;</code></td>
        </tr>
        <tr>
            <td>Footnotes</td>
            <td>footnotes</td>
            <td>link</td>
            <td>link</td>
            <td>footnotes</td>
        </tr>
        <tr>
            <td>Definition lists</td>
            <td>plain text</td>
            <td>plain text</td>
            <td>plain text</td>
            <td><code>&lt;dl&gt;</code></td>
        </tr>
        <tr>
            <td>Attributes <code>{: #id }</code></td>
            <td>plain text</td>
            <td>plain text</td>
            <td>plain text</td>
            <td>applied</td>
        </tr>
        <tr>
            <td>Bare URLs</td>
            <td>plain text</td>
            <td>plain text</td>
            <td>linked</td>
            <td>plain text</td>
        </tr>
        <tr>
            <td>HTML entities</td>
            <td>preserved</td>
            <td>decoded</td>
            <td>decoded</td>
            <td>decoded</td>
        </tr>
        <tr>
            <td>Raw HTML block</td>
            <td>preserved</td>
            <td>preserved</td>
            <td>preserved</td>
            <td>preserved</td>
        </tr>
    </tbody>
</table>

Two details are worth pointing out:

- **HTML entities** such as `&copy;` are decoded by `league/commonmark` and output as the
  character `©`. Parsedown left the entity untouched. This rarely changes what the visitor
  sees, but it changes the generated markup.
- **Footnotes** are written `[^1]` in Parsedown Extra. `league/commonmark` treats the caret as
  the first character of the link target, so `[^1]` becomes a link to `1` rather than a footnote
  reference. Footnotes from Herbie 2.x need to be rewritten.

To get closest to the behaviour of Herbie 2.x, set `flavor` to `markdown-extra`. That restores
tables, footnotes, definition lists, attributes and strikethrough, but it is not identical to
Parsedown Extra.

### Raw HTML blocks

Raw HTML blocks end at the first empty line according to the CommonMark specification. To keep
compatibility with Herbie 2.x, Herbie protects such blocks before parsing, so a block spread
over several paragraphs still renders as HTML:

~~~html
<div>
Absatz 1

Absatz 2
</div>
~~~

Indented code samples that contain HTML remain code blocks, as before.
