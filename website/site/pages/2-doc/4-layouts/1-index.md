---
title: Themes and layouts
layout: doc
twig: false
---

# Themes and layouts

Herbie uses the [Twig](https://twig.symfony.com) template engine for layouts.
A theme provides the templates and assets that determine the appearance of a site; each page selects a layout and fills it with content.

## How themes work

The following terms are used here:

- **Theme** – a directory under `site/themes` that bundles the templates, assets and icons for a site's appearance.
- **Parent theme** – the theme named in `themeParent`, used when a file is missing from the active theme.
- **Template** – any Twig file. Layouts are templates, but base templates and snippets are templates too and are not selectable as layouts.
- **Layout** – a Twig template in the theme that a page selects with `layout:`. Its name is the file name without the extension.
- **Asset** – a CSS, JS or image file referenced through `@theme` and published below `/assets/themes/`.
- **Token** – a CSS custom property provided by a theme and read by plugins.
- **Segment** – a named part of a page's content, available in templates as `content.<name>`.

The active theme is selected globally in `site/config/main.php`; the default is `default`:

~~~php
'theme' => 'my-theme',
'themeParent' => 'default',
~~~

When Herbie renders a page, it takes the layout name from the page's `layout` property and looks for the matching Twig file in this order:

1. `site/themes/<theme>/<layout>.twig` – the active theme
2. `site/themes/<themeParent>/<layout>.twig` – the parent theme (default: `default`)

The first file found wins. A theme therefore only needs to contain the files it wants to change; everything else comes from the parent.
Set `themeParent` to an empty string to disable this fallback.

~~~text
site/themes/
├── default/            # parent: base templates, tokens, icons
└── my-theme/           # active: only the files that differ
    ├── css/styles.css
    └── default.twig
~~~

Assets and icons follow the same order. The `@theme` alias points at the active theme directory, so templates can reference its assets without hardcoding the theme name:

~~~twig
{{ css_add("@theme/css/styles.css") }}
~~~

If the file is missing from the active theme, the parent theme supplies it.
Icons loaded with `icon()` are resolved in the same way.
Published assets keep the active theme in their URL, for example `/assets/themes/<theme>/css/styles.css`.

## Layout files

Layout files are stored under `site/themes/<theme>`, where the last path segment is the name of the active theme.
The layout file extension is configured with `fileExtensions.layouts` and defaults to `twig`.

Herbie expects at least two layout files:

~~~text
site/themes/default/
├── default.twig        # default layout
└── error.twig          # error layout
~~~

A layout is a Twig template in the theme; a page selects it with `layout:`.
Layouts usually extend a base template and fill its blocks with content.
In the following example `main.twig` is the base template, while `default.twig`, `homepage.twig` and `twocolumn.twig` are layouts.

~~~text
site/themes/default/
├── default.twig        # default layout (required)
├── error.twig          # error layout (required)
├── homepage.twig       # homepage layout
├── main.twig           # base template
└── twocolumn.twig      # two-column layout
~~~

## Template inheritance

### Main template

The main (base) template defines blocks that sub templates can fill. A simple example:

~~~twig
# main.twig

<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" href="style.css" />
        <title>{% block title %}{% endblock %} - My Webpage</title>
    </head>
    <body>
        <div id="content">{% block content %}{% endblock %}</div>
        <div id="sidebar">{% block sidebar %}{% endblock %}</div>
        <div id="footer">Copyright 2022 by you.</div>
    </body>
</html>
~~~

The example defines three blocks (`title`, `content` and `sidebar`) without any content.

### Sub template

A sub (child) template extends the main template and fills its blocks:

~~~twig
# default.twig

{% extends "main.twig" %}

{% block title %}Index{% endblock %}

{% block content %}
    <h1>Index</h1>
    <p class="important">Welcome to my homepage!</p>
{% endblock %}
~~~

To make this dynamic, the global `content` variable provides the rendered content of the page segments.
Internally, the segments go through one or more formatting processes such as rendering Twig or parsing shortcode, Markdown or Textile code.
The sub template now looks like this:

~~~twig
# default.twig

{% extends "main.twig" %}

{% block title %}{{ page.title }}{% endblock %}

{% block content %}
    {{ content.default|raw }}
{% endblock %}
~~~

The `title` block takes the page title, the `content` block the page content.
The `raw` filter is required because Twig escapes HTML by default.

To place a sidebar next to the normal content column, fill the `sidebar` block as well:

~~~twig
# twocolumn.twig

{% extends "main.twig" %}

{% block title %}{{ page.title }}{% endblock %}

{% block content %}
    {{ content.default|raw }}
{% endblock %}

{% block sidebar %}
    {{ content.sidebar|raw }}
{% endblock %}
~~~

## Selecting a layout

The `layout` page property applies one of the prepared layouts to a page.
Its value is the file name without the extension, because Herbie appends the extension from `fileExtensions.layouts` (default `twig`).
If `fileExtensions.layouts` is empty, `layout` must contain the full file name.

~~~html
---
title: My Two-Column Page
layout: twocolumn
---

This is content for the default segment.

--- sidebar ---

And here is content for the sidebar segment.
~~~

Further information on how page contents must be formatted can be found in the [Contents](doc/contents) chapter.

## CSS naming conventions

CSS shipped by Herbie uses a `herbie-` prefix for classes and `--herbie-` for custom properties.
Plugin styles add the plugin name as scope, e.g. `.herbie-contact-errors` and `--herbie-contact-error`.

Theme tokens such as `--surface` and `--accent-button` are intentionally unprefixed: they are provided by the active theme and read by plugins with `var(--surface, #ffffff)`.
Tokens owned by third-party libraries (e.g. `--altcha-*`) keep their original names.

## Further reading

Another good resource is the layout files of this website itself.
They are available on GitHub at <https://github.com/getherbie/website/>.
