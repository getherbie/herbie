<?php

declare(strict_types=1);

namespace herbie\events;

use herbie\AbstractEvent;

final class LayoutRenderedEvent extends AbstractEvent
{
    public function __construct(private string $content) {}

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}
