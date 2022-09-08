<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

use Throwable;

class SystemException extends \Exception
{
    public static function classNotExist(string $class, ?string $format = null): SystemException
    {
        $format = $format ?? 'Class "%s" does not exist';
        $message = sprintf($format, $class);
        return new SystemException($message, 500);
    }

    public static function methodNotExist(string $method, ?string $format = null): SystemException
    {
        $format = $format ?? 'Method "%s" does not exist';
        $message = sprintf($format, $method);
        return new SystemException($message, 500);
    }

    public static function includeFileNotExist(string $filepath, ?string $format = null): SystemException
    {
        $format = $format ?? 'Include file "%s" does not exist';
        $message = sprintf($format, $filepath);
        return new SystemException($message, 500);
    }

    public static function directoryNotExist(string $directory, ?string $format = null): SystemException
    {
        $format = $format ?? 'Directory "%s" does not exist';
        $message = sprintf($format, $directory);
        return new SystemException($message, 500);
    }

    public static function directoryNotWritable(string $directory, ?string $format = null): SystemException
    {
        $format = $format ?? 'Directory "%s" is not writable';
        $message = sprintf($format, $directory);
        return new SystemException($message, 500);
    }

    public static function directoryNotReadable(string $directory, ?string $format = null): SystemException
    {
        $format = $format ?? 'Directory "%s" is not readable';
        $message = sprintf($format, $directory);
        return new SystemException($message, 500);
    }

    public static function fileNotExist(string $file, ?string $format = null): SystemException
    {
        $format = $format ?? 'File "%s" does not exist';
        $message = sprintf($format, $file);
        return new SystemException($message, 500);
    }

    public static function pluginNotExist(string $plugin, ?string $format = null): SystemException
    {
        $format = $format ?? 'Plugin "%s" does not exist';
        $message = sprintf($format, $plugin);
        return new SystemException($message, 500);
    }

    public static function serverError(string $message, ?Throwable $t = null): SystemException
    {
        return new SystemException($message, 500, $t);
    }
}
