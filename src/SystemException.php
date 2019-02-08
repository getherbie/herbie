<?php

declare(strict_types=1);

namespace Herbie;

class SystemException extends \Exception
{

    /**
     * @param string $class
     * @param string $format
     * @return static
     */
    public static function classNotExist(string $class, string $format = null): SystemException
    {
        $format = $format ?? 'Class "%s" does not exist';
        $message = sprintf($format, $class);
        return new SystemException($message, 500);
    }

    /**
     * @param string $method
     * @param string $format
     * @return static
     */
    public static function methodNotExist(string $method, string $format = null): SystemException
    {
        $format = $format ?? 'Method "%s" does not exist';
        $message = sprintf($format, $method);
        return new SystemException($message, 500);
    }

    /**
     * @param string $filepath
     * @param string $format
     * @return static
     */
    public static function includeFileNotExist(string $filepath, string $format = null): SystemException
    {
        $format = $format ?? 'Include file "%s" does not exist';
        $message = sprintf($format, $filepath);
        return new SystemException($message, 500);
    }

    /**
     * @param string $directory
     * @param string $format
     * @return static
     */
    public static function directoryNotExist(string $directory, string $format = null): SystemException
    {
        $format = $format ?? 'Directory "%s" does not exist';
        $message = sprintf($format, $directory);
        return new SystemException($message, 500);
    }

    /**
     * @param string $directory
     * @param string $format
     * @return static
     */
    public static function directoryNotWritable(string $directory, string $format = null): SystemException
    {
        $format = $format ?? 'Directory "%s" is not writable';
        $message = sprintf($format, $directory);
        return new SystemException($message, 500);
    }

    /**
     * @param string $directory
     * @param string $format
     * @return static
     */
    public static function directoryNotReadable(string $directory, string $format = null): SystemException
    {
        $format = $format ?? 'Directory "%s" is not readable';
        $message = sprintf($format, $directory);
        return new SystemException($message, 500);
    }

    /**
     * @param string $file
     * @param string $format
     * @return static
     */
    public static function fileNotExist(string $file, string $format = null): SystemException
    {
        $format = $format ?? 'File "%s" does not exist';
        $message = sprintf($format, $file);
        return new SystemException($message, 500);
    }

    /**
     * @param string $message
     * @param \Throwable $t
     * @return static
     */
    public static function serverError(string $message, \Throwable $t = null): SystemException
    {
        return new SystemException($message, 500, $t);
    }
}
