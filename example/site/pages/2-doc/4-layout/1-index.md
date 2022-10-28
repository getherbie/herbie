---
title: Layouts
layout: doc
twig: false
---

# Layouts

Layout files are implemented in Herbie CMS using the Twig template engine.
Twig templates are quite easy to understand and very well documented, see <https://twig.symfony.com/>.

Layout files are stored under `site/themes/default`, where the last path segment stands for the current theme.
Herbie CMS expects at least two layout files:

    site/themes/default/
    |-- default.html        # the default template
    └── error.html          # the error page


It makes sense to divide the layouts into a main template and one or more sub templates.
These sub templates inherit the blocks of the main template and can fill them with content.

    site/themes/default/
    |-- default.html        # the default template (required)
    |-- twocols.html        # the two-column template
    |-- homepage.html       # the homepage template
    |-- error.html          # the error page (required)
    └── main.html           # the main template

A simple main template might look like this:

## Main template

    # main.html

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

In the main template, three blocks (title, content, sidebar) have been defined.
But these blocks do not contain any content yet.


## Sub template

The sub template inherits from the main template and fills the blocks, that are defined in the main template, with content.

    # default.html
    
    {% extends "main.html" %}
    
    {% block title %}Index{% endblock %}
    
    {% block content %}
        <h1>Index</h1>
        <p class="important">Welcome to my homepage!</p>
    {% endblock %}


In order for the whole thing to work dynamically, a content function must be used.
This has the task of outputting the contents of a page segment.
Internally, the function goes through one or more formatting processes such as rendering Twig or parsing shortcode, markdown or Textile code.

So the customized (dynamized) sub template now looks like this:

    # default.html
    
    {% extends "main.html" %}
    
    {% block title %}{{ page.title }}{% endblock %}
    
    {% block content %}  
        {{ content('default') }}
    {% endblock %}


Both the `title` block for the page title and the `content` block for the page content are filled dynamically.

If you want to place a sidebar next to the normal content column, you can do this as follows.

    # twocolumn.html
     
    {% extends "main.html" %}
    
    {% block title %}{{ page.title }}{% endblock %}
    
    {% block content %}  
        {{ content('default') }}
    {% endblock %}     
    
    {% block sidebar %}  
        {{ content('sidebar') }}
    {% endblock %}


The page properties block is now used to apply one of the prepared layouts to each page.

    ---
    title: My Two-Column Page
    layout: twocolumn
    ---

    This is content for the default segment.

    --- sidebar ---

    And here is content for the sidebar segment.


Further information on how page contents must be formatted can be found in the chapter [Contents](doc/contents).

Another good ressource are the layout files of this website itself.
They are available on GitHub at <https://github.com/getherbie/website/>.
