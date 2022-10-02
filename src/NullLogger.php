<?php

namespace herbie;

use Psr\Log\AbstractLogger;

/**
 * Class NullLogger
 * @package Psr\Log
 */
final class NullLogger extends AbstractLogger
{
    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = []): void
    {
        // noop
    }
}
