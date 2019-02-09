<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie;

/**
 * @param string $input
 * @param string $separator
 * @return string
 */
function camelize(string $input, string $separator = '_'): string
{
    return str_replace($separator, '', ucwords($input, $separator));
}

/**
 * @param string $path
 * @return string
 * @throws SystemException
 */
function normalize_path(string $path): string
{
    $realpath = realpath($path);
    if ($realpath === false) {
        $message = sprintf('Could not normalize path "%s"', $path);
        throw SystemException::serverError($message);
    }
    return rtrim($realpath, '/');
}

/**
 * @param \Throwable $exception
 * @return string
 */
function render_exception(\Throwable $exception): string
{
    if (HERBIE_DEBUG) {
        $format = "Uncatched Exception: %s\n\n%s [%s] in %s on line %s\n\nStack trace:\n%s";
        $message = sprintf(
            $format,
            $exception->getMessage(),
            get_class($exception),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        // remove path
        $path = realpath(__DIR__ . '/../../');
        $message = str_replace($path, '', $message);
    } else {
        $format = 'Uncatched Exception: %s';
        $message = sprintf($format, $exception->getMessage());
    }

    return sprintf('<pre>%s</pre>', $message);
}
