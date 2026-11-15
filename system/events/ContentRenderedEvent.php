<?php

declare(strict_types=1);

namespace herbie\events;

use herbie\AbstractEvent;

final class ContentRenderedEvent extends AbstractEvent
{
    /**
     * @param array<string, string> $segments
     */
    public function __construct(private array $segments)
    {
    }

    /**
     * @return array<string, string>
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     * @param array<string, string> $segments
     */
    public function setSegments(array $segments): void
    {
        $this->segments = $segments;
    }
}
