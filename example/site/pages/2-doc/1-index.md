---
title: Documentation
layout: doc
---

# Documentation

This documentation teaches the most important topics to create a website or blog with Herbie CMS.

## What exactly is Herbie CMS?

Herbie CMS is a content management system based on flat-files and does not require a database.
The content of the website is loaded from the file system from simple text files.
The program runs through a defined directory with text files, converts these files according to their file extension and outputs them - embedded in an HTML layout - as a complete website.

Herbie CMS is influenced by flat-file CMS systems from the Ruby, Go and PHP world. 
Among them are names like [Grav][4], [Hugo][3], [Jekyll][1], [Stacey][5] or [Statamic][2], just to name a few.
The project was born out of the need to have a system available to easily and quickly implement websites.
The development was based on a current version of PHP and the use of proven concepts and components.

<ul>
    {% for item in site.pageList|filter("parentRoute=doc") %}
    <li><a href="{{ item.route }}">{{ item.title }}</a></li>
    {% endfor %}
</ul>

[1]: http://jekyllrb.com
[2]: http://statamic.com
[3]: http://gohugo.io
[4]: http://getgrav.org
[5]: http://www.staceyapp.com
