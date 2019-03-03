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

/**
 * Recursively delete a directory and all of it's contents - e.g.the equivalent of `rm -r` on the command-line.
 * Consistent with `rmdir()` and `unlink()`, an E_WARNING level error will be generated on failure.
 *
 * @see http://stackoverflow.com/a/3352564/283851
 * @param string $dir absolute path to directory to delete
 * @return bool true on success; false on failure
 */
function remove_folder(string $dir): bool
{
    if (false === file_exists($dir)) {
        return false;
    }

    /** @var \SplFileInfo[] $files */
    $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        if ($fileinfo->isDir()) {
            if (false === rmdir($fileinfo->getRealPath())) {
                return false;
            }
        } else {
            if (false === unlink($fileinfo->getRealPath())) {
                return false;
            }
        }
    }

    return rmdir($dir);
}

/**
 * @param string $path
 * @param array|null $skip
 * @return bool
 */
function empty_folder(string $path, ?array $skip = null): bool
{
    if (is_null($skip)) {
        $skip = ['.', '..', '.gitignore'];
    }
    /** @var \SplFilInfo[] $files */
    $files = new \DirectoryIterator($path);
    $success = true;
    foreach ($files as $fileInfo) {
        if (in_array($fileInfo->getFilename(), $skip)) {
            continue;
        }
        $realPath = $fileInfo->getRealPath();
        if ($fileInfo->isDir()) {
            $success &= remove_folder($realPath);
        } else {
            $success &= unlink($realPath);
        }
    }
    return boolval($success);
}

/**
 * @param string $path
 * @param array|null $skip
 * @return int
 */
function count_files(string $path, ?array $skip = null): int
{
    if (is_null($skip)) {
        $skip = ['.', '..', '.gitignore', '.DS_Store'];
    }
    /** @var \SplFileInfo[] $files */
    $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($path),
        \RecursiveIteratorIterator::SELF_FIRST
    );
    $count = 0;
    foreach ($files as $fileInfo) {
        if (in_array($fileInfo->getFilename(), $skip)) {
            continue;
        }
        $count++;
    }
    return $count;
}
