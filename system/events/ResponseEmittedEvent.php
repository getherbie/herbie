<?php

declare(strict_types=1);

namespace herbie\events;

use herbie\AbstractEvent;
use herbie\Application;

final class ResponseEmittedEvent extends AbstractEvent
{
    public function __construct(private Application $application)
    {
    }

    public function getApplication(): Application
    {
        return $this->application;
    }
}
