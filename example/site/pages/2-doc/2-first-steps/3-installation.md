---
title: Installation
layout: doc
---

# Installation


## System requirements

There are only a few host system requirements that must be met.
These are:

- Operating system: Windows, Linux or Mac
- PHP: >=7.4
- Composer: >=2.x


## Composer support

The easiest way to install Herbie CMS is via Composer.
To do this, run the following command in the terminal:

    composer create-project getherbie/start-website mywebsite

Composer creates a website template in the *mywebsite* directory and installs all dependent packages.

Tip: To speed up the installation and keep the vendor directory as lean as possible, you can use the `--prefer-dist` option.

    composer create-project --prefer-dist getherbie/start-website mywebsite

It may be necessary to recursively change the owner of the created directory.
This depends on the host system and its settings.

    chown -R new-owner mywebsite

Then change to the `web` directory of the created project and start the internal PHP webserver.

    cd mywebsite/web
    php -S localhost:8888 index.php

The website can now be published in the browser under `http://localhost:8888`.
