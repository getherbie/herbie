---
title: Example plugins
layout: doc
---

# Example plugins

Seven complete plugins for `site/extend/plugins`, ordered by complexity.

- **[Random Page](doc/extending/examples/random)** – event listener, page list, page segments.
- **[GitHub Gist](doc/extending/examples/gist)** – Twig function, HTML escaping.
- **[MathJax Renderer](doc/extending/examples/mathjax)** – config defaults, Twig filter, assets.
- **[Grid System](doc/extending/examples/grid)** – event priority, regex replacement, config.
- **[Disqus Comments](doc/extending/examples/disqus)** – Twig template, configurable template path.
- **[Video Embeds](doc/extending/examples/video)** – two Twig functions, shared template, input validation.
- **[Lipsum Generator](doc/extending/examples/lipsum)** – helper class, several Twig functions.

Copy a directory into `site/extend/plugins`, add its name to `enabledPlugins`, and run `herbie clear-files` outside debug mode.
