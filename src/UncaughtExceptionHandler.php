<?php

/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie;

final class UncaughtExceptionHandler
{
    public function __invoke(\Throwable $exception): void
    {
        if (!headers_sent()) {
            header("HTTP/1.1 " . $exception->getCode());
        }

        echo render_exception($exception);
        exit(1);
    }
}
