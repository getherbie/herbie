<?php

use herbie\events\RenderSegmentEvent;

$renderSegmentEvent = function (RenderSegmentEvent $event) {
    $page = $event->getPage();
    $parentRoute = $page->getParentRoute();
    if (strpos($parentRoute, 'doc') === false) {
        return;
    }

    if ($event->getSegmentId() !== 'default') {
        return;
    }

    $segment = $event->getSegment();

    if (strpos($segment, '<h1>') === false) {
        return;
    }

    preg_match_all('#<h([2])>(.+?)</h\1>#i', $segment, $matches);

    if (!isset($matches[0]) || (count($matches[0]) === 0)) {
        return;
    }

    $urlManager = $event->getUrlManager();

    $links = [];
    $replace = [];
    foreach ($matches[0] as $index => $search) {
        $id = trim(preg_replace('/[^a-zA-z0-9]/', '-', strtolower($matches[2][$index])), '-');
        $url = $urlManager->createUrl($page->getRoute());
        $replace[] = [
            $matches[0][$index],
            '<h' . $matches[1][$index] . ' id="' . $id . '">' . $matches[2][$index] . '</h' . $matches[1][$index] . '>'
        ];
        $links[] = sprintf('<li><a href="%s#%s">%s</a></li>', $url, $id, $matches[2][$index]);
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
        $segment
    );

    $event->setSegment($segment);
};

return [RenderSegmentEvent::class, $renderSegmentEvent];
