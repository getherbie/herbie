<?php

declare(strict_types=1);

namespace Herbie;

use Herbie\Exception\SystemException;

/**
 * @param string $input
 * @param string $separator
 * @return string
 */
function camelize($input, $separator = '_')
{
    return str_replace($separator, '', ucwords($input, $separator));
}

/**
 * @param string $path
 * @return string
 * @throws SystemException
 */
function normalize_path(string $path)
{
    $realpath = realpath($path);
    if ($realpath === false) {
        $message = sprintf('Could not normalize path "%s"', $path);
        throw SystemException::serverError($message);
    }
    return rtrim($realpath, '/');
}
