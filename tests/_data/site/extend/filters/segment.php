<?php

declare(strict_types=1);

namespace tests\_data\site\extend\filters;

use herbie\event\RenderSegmentEvent;

return [RenderSegmentEvent::class, function (RenderSegmentEvent $event): void {
    $segment = $event->getSegment()
        . '<div style="display:none" class="example-site-extend-filters-segment">'
        . __FUNCTION__
        . '</div>';
    $event->setSegment($segment);
}];
