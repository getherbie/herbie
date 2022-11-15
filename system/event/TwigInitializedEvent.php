<?php

declare(strict_types=1);

namespace herbie\event;

use herbie\AbstractEvent;
use herbie\TwigRenderer;

final class TwigInitializedEvent extends AbstractEvent
{
    private TwigRenderer $twigRenderer;

    public function __construct(TwigRenderer $twigRenderer)
    {
        $this->twigRenderer = $twigRenderer;
    }

    public function getTwigRenderer(): TwigRenderer
    {
        return $this->twigRenderer;
    }
}
