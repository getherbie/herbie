<?php

declare(strict_types=1);

namespace herbie\tests\_data\src;

use herbie\events\RenderSegmentEvent;

class TestFilter
{
    public function __invoke(RenderSegmentEvent $event)
    {
        // do someghint with $event
    }
}
