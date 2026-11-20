---
title: Random Page
layout: doc
---

# Random Page

The Random Page plugin is the smallest useful example: a page with the `random` property shows the content of a randomly chosen other page.
It demonstrates a single **event listener** that modifies the page before its segments are rendered.

## Structure

~~~text
site/extend/plugins/random/
└── RandomPlugin.php
~~~

## Code

~~~php
<?php

declare(strict_types=1);

namespace herbie\plugins\random;

use herbie\AbstractPlugin;
use herbie\events\RenderPageEvent;
use herbie\Page;
use herbie\PageRepositoryInterface;
use herbie\Plugin;

#[Plugin('random')]
final class RandomPlugin extends AbstractPlugin
{
    public function __construct(private readonly PageRepositoryInterface $pageRepository) {}

    public function eventListeners(): array
    {
        return [
            [RenderPageEvent::class, $this->onRenderPage(...)],
        ];
    }

    public function onRenderPage(RenderPageEvent $event): void
    {
        $page = $event->getPage();
        if (!isset($page->random)) {
            return;
        }

        $excludes = (array)($page->random['excludes'] ?? []);
        $candidates = $this->pageRepository->findAll()->filter(
            static fn(Page $item): bool => !$item->getHidden()
                && $item->getRoute() !== $page->getRoute()
                && !in_array($item->getRoute(), $excludes, true),
        );

        $selected = $candidates->shuffle()->getIterator()->current();
        if (!$selected instanceof Page) {
            return;
        }

        // Take over the content of the selected page, but keep the current route.
        $page->setSegments($selected->getSegments());
        $page->setTitle($selected->getTitle());
    }
}
~~~

## Usage

Enable the plugin in `site/config/main.php`:

~~~php
'enabledPlugins' => 'twig,markdown,random',
~~~

Then add the property to a page:

~~~markdown
---
title: Surprise me
random:
  excludes:
    - doc
---

This text is replaced by a random page.
~~~

`PageList::shuffle()` returns the pages in random order while keeping their routes as keys.
The listener runs on `RenderPageEvent`, so it happens before Markdown, Twig and the layout are applied.
