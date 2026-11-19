---
title: Layouts
layout: doc
twig: false
---

# Layouts

Layout files are implemented in Herbie using the Twig template engine.
Twig templates are quite easy to understand and very well documented.

The Twig documentation can be found at <https://twig.symfony.com>.

Layout files are stored under `site/themes/default`, where the last path segment stands for the current theme.
The layout file extension is configured with `fileExtensions.layouts` and defaults to `twig`.
Use the `@theme` alias to reference assets of the active theme without hardcoding its name, e.g. `{{ css_add("@theme/css/styles.css") }}`.
Templates missing from the active theme are looked up in the parent theme configured with `themeParent` (default: `default`). Assets referenced through `@theme` and icons loaded with `icon()` use the same fallback: the active theme wins, the parent theme supplies everything it does not provide. Set `themeParent` to an empty string to disable the fallback.

## CSS naming conventions

CSS shipped by Herbie uses a `herbie-` prefix for classes and `--herbie-` for custom properties. Plugin styles add the plugin name as scope, e.g. `.herbie-contact-errors` and `--herbie-contact-error`.

Theme tokens such as `--surface` and `--accent-button` are intentionally unprefixed: they are provided by the active theme and read by plugins with `var(--surface, #ffffff)`. Tokens owned by third-party libraries (e.g. `--altcha-*`) keep their original names.

Herbie expects at least two layout files:

~~~text
site/themes/default/
├── default.twig        # default page
└── error.twig          # error template
~~~

Often it makes sense to divide the layouts into a main template and one or more sub templates.
These sub templates inherit the blocks of the main template and can fill them with content.

~~~text
site/themes/default/
├── default.twig        # default template (required)
├── error.twig          # error page (required)
├── homepage.twig       # homepage template
├── main.twig           # main template
└── twocolumn.twig      # two-column template
~~~

A simple main template might look like this:

## Main template

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

In the main template, three blocks (title, content, sidebar) have been defined.
But these blocks do not contain any content yet.

## Sub template

The sub template inherits from the main template and fills the blocks with content, that are defined in the main template.

~~~twig
# default.twig

{% extends "main.twig" %}

{% block title %}Index{% endblock %}

{% block content %}
    <h1>Index</h1>
    <p class="important">Welcome to my homepage!</p>
{% endblock %}
~~~


In order for the whole thing to work dynamically, the global `content` variable provides the rendered content of the page segments.
Internally, the segments go through one or more formatting processes such as rendering Twig or parsing shortcode, markdown or Textile code.

So, the customized (dynamized) sub template now looks like this:

~~~twig
# default.twig

{% extends "main.twig" %}

{% block title %}{{ page.title }}{% endblock %}

{% block content %}
    {{ content.default|raw }}
{% endblock %}
~~~

Both the `title` block for the page title and the `content` block for the page content are filled dynamically.
The `raw` filter is required because Twig escapes HTML by default.

If you want to place a sidebar next to the normal content column, you can do this as follows.

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

The page properties block is now used to apply one of the prepared layouts to each page.
The value of `layout` is the file name without the extension, because Herbie appends the extension from `fileExtensions.layouts` (default `twig`).
If the option is empty, `layout` must contain the full file name.

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

Another good resource are the layout files of this website itself.
They are available on GitHub at <https://github.com/getherbie/website/>.
