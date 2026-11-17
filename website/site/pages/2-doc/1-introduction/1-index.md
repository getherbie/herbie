---
title: Introduction
layout: doc
---

# Introduction

This documentation explains the most important topics for creating a website or blog with Herbie.

## What exactly is Herbie?

Herbie is a content management system based on flat-files and therefore does not require a database.
The content of the website is loaded from the file system as simple text files.
The program goes through a defined directory of text files, converts these files according to their file extension and outputs them, embedded in an HTML layout, as a complete website.

A Herbie website is a plain directory tree of Markdown, Textile, reStructuredText or plain text files plus Twig layout files.
Blog content works the same way: posts are pages, feeds such as `rss.xml` or `atom.xml` are pages with the appropriate `content_type`, and the `search` plugin provides full-text search over all pages.

Herbie is influenced by flat-file CMS systems from the Ruby, Go and PHP world.
Among them are names like [Grav][4], [Hugo][3], [Jekyll][1] and [Statamic][2], just to name a few.
The project was born out of the need to have a system available to realize websites easily and quickly.
The development is based on a current version of PHP and on proven concepts and components.

## Where is the name coming from?

The name comes from Herbie Hancock, one of the most influential jazz musicians of all time.
The project is dedicated to him and his work.

[1]: https://jekyllrb.com
[2]: https://statamic.com
[3]: https://gohugo.io
[4]: https://getgrav.org
