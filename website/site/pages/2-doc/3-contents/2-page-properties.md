---
title: Page Properties
layout: doc
---

# Page Properties

Any file that contains a page properties block as known as front matter is considered a valid page by Herbie.
The page properties block must be at the beginning of the file.
There must be valid YAML between two lines of three hyphens.
This sounds a bit complicated, but it is quite simple.
Here is an example for a page properties block:

    ---
    title: Get started with your own website
    layout: default.html
    ---

Predefined variables (see reference below) or custom variables can be used as page properties.
These variables are available in all layout and content files as page variables.
Here is an example:

{% verbatim %}
    {{ page.title }}
    {{ page.layout }}
{% endverbatim %}

## Predefined variables

There are some predefined and reserved variables that are used by the system.
These can be assigned a value in the page properties block of a page.

{{ snippet("@site/snippets/variables.twig", {type:"vars_page"}) }}

## Custom variables

Any custom variable in the page properties block that is not predefined will be automatically recognized by Herbie and made available in the layout files and page content.
For example, if you declare a variable `class`, you can retrieve it in the layout file and use it to set a CSS class.

In the page properties you declare the value of the variable:

    ---
    title: Welcome to my website!
    class: home
    ---

In the page content itself, you can access these variables as follows:

{% verbatim %}
    {{ page.title }}
    {{ page.layout }}
{% endverbatim %}

And in layout files you output the variables the same:

{% verbatim %}
~~~html
<!DOCTYPE HTML>
<html>
<head>
    <title>{{ page.title }}</title>
</head>
<body class="{{ page.class }}">
    ...
</body>
</html>
~~~
{% endverbatim %}

This allows page variables to be used and enriched with additional custom variables of your choice.
