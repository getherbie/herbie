<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

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
        $format = "%s [%s] in %s on line %s\n\n%s\n\nStack trace:\n%s";
        $message = sprintf(
            $format,
            get_class($exception),
            $exception->getCode(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getMessage(),
            strip_tags($exception->getTraceAsString())
        );

        // remove path
        $path = realpath(__DIR__ . '/../../');
        $message = str_replace($path, '', $message);
        return sprintf('<pre class="error error--exception error--debug">%s</pre>', $message);
    }

    $format = '%s';
    $message = sprintf($format, $exception->getMessage());
    return sprintf('<pre class="error error--exception">%s</pre>', $message);
}

/**
 * @param string $list
 * @param string $delim
 * @return array
 */
function explode_list(string $list, string $delim = ',')
{
    $list = trim($list);
    if (strlen($list) === 0) {
        return [];
    }
    $values = explode($delim, $list);
    $values = array_map('trim', $values);
    return $values;
}
