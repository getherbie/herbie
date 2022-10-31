---
title: Structure of a page
layout: doc
---

# Structure of a page

In the previous chapters it was explained that a page corresponds to a text file in the page directory.
In the simplest case, a text file looks something like this:

    ---
    title: A simple page
    ---

    The content about the simple page.

Herbie CMS creates a page title and adds the text to the default content segment below the page properties.


## Named content segments

Unfortunately, most websites are not that simple.
Usually they are based on multi-column layouts that you want to fill independently.
This can be achieved with Herbie CMS by using named content segments.
A named content segment is defined with three minus signs followed by a string and another three minus signs, for example:

    --- sidebar ---

The following text is then assigned to the content segment with ID sidebar.
Let's look at a slightly more complicated example:

    ---
    title: A page with content segments
    ---

    Content is assigned to the default segment.

    --- left ---

    Content is assigned to the left segment.

    --- right ---

    Content is assigned to the right segment.


With this simple rule you can fill content of a page into several containers and address them within the layout.
In this way, more complex layouts can be handled even with simple text files.


## Render content segments in layout

In layout files content segments are rendered using a content function.

{% verbatim %}
    {{ content('default') }}
{% endverbatim %}

The content function expects the segment ID as the only parameter.
If no parameter is specified, the default content segment is rendered.

{% verbatim %}
    <body>
        <div class="segment-default">
            {{ content('default') }}
        </div>
        <div class="segment-left">
            {{ content('left') }}
        </div>
        <div class="segment-right">
            {{ content('right') }}
        </div>
    </body>
{% endverbatim %}

An illustrative example can also be found in the website repository at GitHub.
