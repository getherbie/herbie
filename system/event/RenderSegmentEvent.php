<?php

declare(strict_types=1);

namespace herbie\event;

use herbie\AbstractEvent;

final class RenderSegmentEvent extends AbstractEvent
{
    private string $segment;
    private string $segmentId;
    private bool $enableTwig;
    private string $formatter;

    public function __construct(string $segment, string $segmentId, bool $enableTwig, string $formatter)
    {
        $this->segment = $segment;
        $this->segmentId = $segmentId;
        $this->enableTwig = $enableTwig;
        $this->formatter = $formatter;
    }

    public function getSegment(): string
    {
        return $this->segment;
    }

    public function getSegmentId(): string
    {
        return $this->segmentId;
    }

    public function enableTwig(): bool
    {
        return $this->enableTwig;
    }

    public function getFormatter(): string
    {
        return $this->formatter;
    }

    public function setSegment(string $segment): void
    {
        $this->segment = $segment;
    }
}
