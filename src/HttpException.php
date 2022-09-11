<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

final class HttpException extends \Exception
{
    public static function notFound(string $path, ?string $format = null): HttpException
    {
        $format = $format ?? 'Cannot find any resource at "%s"';
        $message = sprintf($format, $path);
        return new HttpException($message, 404);
    }

    public static function fileNotFound(string $path, ?string $format = null): HttpException
    {
        $format = $format ?? 'File "%s" not found';
        $message = sprintf($format, $path);
        return new HttpException($message, 404);
    }

    public static function methodNotAllowed(string $path, string $method, ?string $format = null): HttpException
    {
        $format = $format ?? 'Cannot access resource "%s" using method "%s"';
        $message = sprintf($format, $path, $method);
        return new HttpException($message, 405);
    }

    public static function badRequest(string $path, ?string $format = null): HttpException
    {
        $format = $format ?? 'Cannot parse request "%s"';
        $message = sprintf($format, $path);
        return new HttpException($message, 400);
    }
}
