<?php

use herbie\EventInterface;
use herbie\TwigRenderer;
use Twig\TwigFilter;

return ['onTwigInitialized', function (EventInterface $event): void {
    /** @var TwigRenderer $twigRenderer */
    $twigRenderer = $event->getTarget();
    $twigRenderer->addFilter(new TwigFilter('my_filter2', function (string $content): string {
        return $content . ' My Filter 2';
    }));
}];
