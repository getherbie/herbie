---
title: Installation
layout: doc
---

# Installation

## System requirements

There are only a few host system requirements that must be met.
These are:

- Operating system: Windows, Linux or Mac
- PHP: >=8.0
- Composer: >=2.x

## Composer support

The easiest way to install Herbie is with Composer.
To do this, run the following command in the terminal:

    composer create-project getherbie/start-website mywebsite

Composer creates a website template in the *mywebsite* directory and installs all dependent packages.

It may be necessary to recursively change the owner of the created directory.
This depends on the host system and its settings.

    chown -R new-owner mywebsite

Then change to the `web` directory of the created project and start the internal PHP webserver.

    cd mywebsite/web
    php -S localhost:8888 index.php

The website can now be opened in the browser at <http://localhost:8888>.
