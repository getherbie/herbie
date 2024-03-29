<?php

declare(strict_types=1);

namespace herbie;

use Throwable;

final class UncaughtExceptionHandler
{
    public function __invoke(Throwable $exception): void
    {
        if (!headers_sent()) {
            header("HTTP/1.1 " . $exception->getCode());
        }

        echo render_exception($exception);
        exit(1);
    }
}
