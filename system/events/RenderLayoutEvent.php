<?php

declare(strict_types=1);

namespace herbie\events;

use herbie\AbstractEvent;

final class RenderLayoutEvent extends AbstractEvent
{
    private string $content;
    private array $segments;
    private string $layout;

    public function __construct(array $segments, string $layout)
    {
        $this->content = '';
        $this->segments = $segments;
        $this->layout = $layout;
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
