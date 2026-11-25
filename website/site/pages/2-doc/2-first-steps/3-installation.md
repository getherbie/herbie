---
title: Installation
layout: doc
---

# Installation

## System requirements

There are only a few host system requirements that must be met.
These are:

- Operating system: Windows, Linux or Mac
- Either Docker with Docker Compose (recommended) or PHP >=8.3 with Composer >=2.x

## With Docker and Docker Compose (recommended)

Create project and install dependencies.

~~~bash
docker run --rm -v "$PWD":/app composer create-project --ignore-platform-reqs getherbie/start-website mywebsite
~~~

Go to the `mywebsite` directory and start website.

~~~bash
cd mywebsite
docker compose up website
~~~

Open <http://localhost:8888> with your browser.

## With PHP and Composer

If PHP and Composer are available on your machine, you can install Herbie directly with Composer.
To do this, run the following command in the terminal:

~~~bash
composer create-project getherbie/start-website mywebsite
~~~

Composer creates a website template in the *mywebsite* directory and installs all dependent packages.

It may be necessary to recursively change the owner of the created directory.
This depends on the host system and its settings.

~~~bash
chown -R new-owner mywebsite
~~~

Then change to the `web` directory of the created project and start the internal PHP webserver.

~~~bash
cd mywebsite/web
php -S localhost:8888 index.php
~~~

The website can now be opened in the browser at <http://localhost:8888>.

Always start the built-in server with `index.php` as router script. Without it, PHP itself answers
requests for files that do not exist in the `web` directory, such as `/media/logo.png`, with a 404
error before Herbie is called.
