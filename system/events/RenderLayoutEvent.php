<?php

declare(strict_types=1);

namespace herbie\events;

use herbie\AbstractEvent;

final class RenderLayoutEvent extends AbstractEvent
{
    private string $content = '';

    public function __construct(private array $segments, private readonly string $layout)
    {
        $this->content = '';
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function getSegments(): array
    {
        return $this->segments;
    }

    public function unsetSegments(): void
    {
        $this->segments = [];
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}
