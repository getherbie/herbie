<?php

declare(strict_types=1);

namespace herbie\events;

use herbie\AbstractEvent;
use herbie\Application;

final class ResponseEmittedEvent extends AbstractEvent
{
    private Application $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function getApplication(): Application
    {
        return $this->application;
    }
}
