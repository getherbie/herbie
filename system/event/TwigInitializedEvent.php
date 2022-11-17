<?php

declare(strict_types=1);

namespace herbie\event;

use herbie\AbstractEvent;
use herbie\TwigRenderer;
use Twig\Environment;

final class TwigInitializedEvent extends AbstractEvent
{
    private Environment $environment;

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }
}
