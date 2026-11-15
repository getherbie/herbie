<?php

declare(strict_types=1);

namespace herbie\events;

use herbie\AbstractEvent;
use Twig\Environment;

final class TwigInitializedEvent extends AbstractEvent
{
    public function __construct(private Environment $environment)
    {
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }
}
