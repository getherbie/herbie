<?php

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
