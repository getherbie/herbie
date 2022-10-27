<?php

declare(strict_types=1);

namespace herbie;

use Psr\Log\AbstractLogger;

/**
 * Class NullLogger
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
