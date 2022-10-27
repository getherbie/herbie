<?php

declare(strict_types=1);

namespace tests\_data\site\extend\events;

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
