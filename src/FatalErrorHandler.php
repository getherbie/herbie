<?php

declare(strict_types=1);

namespace herbie;

final class FatalErrorHandler
{
    public function __invoke(): void
    {
        $error = error_get_last();
        if ($error && $this->isFatalError($error)) {
            if (!headers_sent()) {
                header("HTTP/1.1 500");
            }
            $exception = new \ErrorException(
                $error['message'],
                500,
                $error['type'],
                $error['file'],
                $error['line']
            );
            echo render_exception($exception);
            exit(1);
        }
    }

    public function isFatalError(array $error): bool
    {
        $errorTypes = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        return isset($error['type']) && in_array($error['type'], $errorTypes);
    }
}
