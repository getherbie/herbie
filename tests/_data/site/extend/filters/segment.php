<?php

declare(strict_types=1);

namespace herbie\tests\_data\site\extend\filters;

use herbie\events\RenderSegmentEvent;

return [
    RenderSegmentEvent::class,
    function (RenderSegmentEvent $event): void {
        $segment = $event->getSegment()
            . '<div style="display:none" class="example-site-extend-filters-segment">'
            . __FUNCTION__
            . '</div>';
        $event->setSegment($segment);
    }
];
