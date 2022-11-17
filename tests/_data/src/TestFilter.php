<?php

declare(strict_types=1);

namespace tests\_data\src;

use herbie\event\RenderSegmentEvent;

class TestFilter
{
    public function __invoke(RenderSegmentEvent $event)
    {
        // do someghint with $event
    }
}
