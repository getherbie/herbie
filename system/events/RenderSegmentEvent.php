<?php

declare(strict_types=1);

namespace herbie\events;

use herbie\AbstractEvent;
use herbie\Page;
use herbie\UrlManager;

final class RenderSegmentEvent extends AbstractEvent
{
    public function __construct(
        private Page $page,
        private string $segment,
        private string $segmentId,
        private UrlManager $urlManager,
    ) {}


    public function getPage(): Page
    {
        return $this->page;
    }

    public function getSegment(): string
    {
        return $this->segment;
    }

    public function setSegment(string $segment): void
    {
        $this->segment = $segment;
    }

    public function getSegmentId(): string
    {
        return $this->segmentId;
    }

    public function getUrlManager(): UrlManager
    {
        return $this->urlManager;
    }
}
