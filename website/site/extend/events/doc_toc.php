<?php

use herbie\events\RenderSegmentEvent;

$renderSegmentEvent = function (RenderSegmentEvent $event): void {
    $page = $event->getPage();
    $parentRoute = $page->getParentRoute();
    if (!str_contains($parentRoute, 'doc')) {
        return;
    }

    if ($event->getSegmentId() !== 'default') {
        return;
    }

    $segment = $event->getSegment();

    if (!str_contains($segment, '<h1>')) {
        return;
    }

    preg_match_all('#<h2\b([^>]*)>(.+?)</h2>#i', $segment, $matches, PREG_SET_ORDER);

    if (count($matches) === 0) {
        return;
    }

    $urlManager = $event->getUrlManager();

    $links = [];
    $replace = [];
    foreach ($matches as $match) {
        $attrs = trim($match[1]);
        $text = $match[2];

        if (str_contains($attrs, 'data-toc-group')) {
            $links[] = sprintf('<li class="toc-group">%s</li>', $text);
            continue;
        }

        // Keep the legacy behaviour: only plain <h2> headings become TOC entries.
        if ($attrs !== '') {
            continue;
        }

        $id = trim(preg_replace('/[^a-zA-z0-9]/', '-', strtolower($text)), '-');
        $url = $urlManager->createUrl($page->getRoute());
        $replace[] = [
            $match[0],
            '<h2 id="' . $id . '">' . $text . '</h2>',
        ];
        $links[] = sprintf('<li><a href="%s#%s">%s</a></li>', $url, $id, $text);
    }

    // replace headings
    $segment = str_replace(array_column($replace, 0), array_column($replace, 1), $segment);

    // append table of contents
    $segment = str_replace(
        '</h1>',
        '</h1>'
        . '<div class="toc">'
        . '<div class="toc-title">On this page</div>'
        . '<ul class="toc-links">' . join('', $links) . '</ul>'
        . '</div>',
        $segment,
    );

    $event->setSegment($segment);
};

return [RenderSegmentEvent::class, $renderSegmentEvent];
