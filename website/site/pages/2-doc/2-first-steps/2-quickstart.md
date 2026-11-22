---
title: Quickstart
layout: doc
---

# Quickstart

Here is the short version of how to install a simple Herbie website using a website template.

<p><a class="pure-button" href="https://start.herbie.tebe.ch/">View demo</a></p>

With Docker and Docker Compose (recommended):

~~~bash
docker run --rm -v "$PWD":/app composer create-project --ignore-platform-reqs getherbie/start-website mywebsite
cd mywebsite
docker compose up website
~~~

Without Docker (PHP and Composer required):

~~~bash
composer create-project getherbie/start-website mywebsite
cd mywebsite/web
php -S localhost:8888 index.php
~~~

That's it!

After that, the website <http://localhost:8888> can be opened in the browser.

For the above commands, however, basic knowledge of using the console and Composer, the dependency manager for PHP, is required.
But it's really not witchcraft and shouldn't be too difficult.

It gets more exciting when you customize the layout, add new pages, or edit content.

If problems have already occurred at this point, the first thing you should do is check all system requirements. 
