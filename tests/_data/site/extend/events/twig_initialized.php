<?php

declare(strict_types=1);

namespace tests\_data\site\extend\events;

use herbie\event\TwigInitializedEvent;
use Twig\TwigFilter;

return [TwigInitializedEvent::class, function (TwigInitializedEvent $event): void {
    $event->getEnvironment()->addFilter(new TwigFilter('my_filter2', function (string $content): string {
        return $content . ' My Filter 2';
    }));
}];
