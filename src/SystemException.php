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

    /**
     * @param string $class
     * @param string|null $format
     * @return SystemException
     */
    public static function classNotExist(string $class, ?string $format = null): SystemException
    {
        $format = $format ?? 'Class "%s" does not exist';
        $message = sprintf($format, $class);
        return new SystemException($message, 500);
    }

    /**
     * @param string $method
     * @param string|null $format
     * @return SystemException
     */
    public static function methodNotExist(string $method, ?string $format = null): SystemException
    {
        $format = $format ?? 'Method "%s" does not exist';
        $message = sprintf($format, $method);
        return new SystemException($message, 500);
    }

    /**
     * @param string $filepath
     * @param string|null $format
     * @return SystemException
     */
    public static function includeFileNotExist(string $filepath, ?string $format = null): SystemException
    {
        $format = $format ?? 'Include file "%s" does not exist';
        $message = sprintf($format, $filepath);
        return new SystemException($message, 500);
    }

    /**
     * @param string $directory
     * @param string|null $format
     * @return SystemException
     */
    public static function directoryNotExist(string $directory, ?string $format = null): SystemException
    {
        $format = $format ?? 'Directory "%s" does not exist';
        $message = sprintf($format, $directory);
        return new SystemException($message, 500);
    }

    /**
     * @param string $directory
     * @param string|null $format
     * @return SystemException
     */
    public static function directoryNotWritable(string $directory, ?string $format = null): SystemException
    {
        $format = $format ?? 'Directory "%s" is not writable';
        $message = sprintf($format, $directory);
        return new SystemException($message, 500);
    }

    /**
     * @param string $directory
     * @param string|null $format
     * @return SystemException
     */
    public static function directoryNotReadable(string $directory, ?string $format = null): SystemException
    {
        $format = $format ?? 'Directory "%s" is not readable';
        $message = sprintf($format, $directory);
        return new SystemException($message, 500);
    }

    /**
     * @param string $file
     * @param string|null $format
     * @return SystemException
     */
    public static function fileNotExist(string $file, ?string $format = null): SystemException
    {
        $format = $format ?? 'File "%s" does not exist';
        $message = sprintf($format, $file);
        return new SystemException($message, 500);
    }

    /**
     * @param string $plugin
     * @param string|null $format
     * @return SystemException
     */
    public static function pluginNotExist(string $plugin, ?string $format = null): SystemException
    {
        $format = $format ?? 'Plugin "%s" does not exist';
        $message = sprintf($format, $plugin);
        return new SystemException($message, 500);
    }

    /**
     * @param string $message
     * @param Throwable|null $t
     * @return SystemException
     */
    public static function serverError(string $message, ?Throwable $t = null): SystemException
    {
        return new SystemException($message, 500, $t);
    }
}
