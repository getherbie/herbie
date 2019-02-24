<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

class HttpException extends \Exception
{
    /**
     * @var array
     */
    private $allowed = [];

    /**
     * @param string $path
     * @param string|null $format
     * @return static
     */
    public static function notFound(string $path, string $format = null): HttpException
    {
        $format = $format ?? 'Cannot find any resource at `%s`';
        $message = sprintf($format, $path);
        return new HttpException($message, 404);
    }

    /**
     * @param string $path
     * @param string|null $format
     * @return static
     */
    public static function fileNotFound(string $path, string $format = null): HttpException
    {
        $format = $format ?? 'File `%s` not found';
        $message = sprintf($format, $path);
        return new HttpException($message, 404);
    }

    /**
     * @param string $path
     * @param string $method
     * @param array $allowed
     * @param string|null $format
     * @return static
     */
    public static function methodNotAllowed(
        string $path,
        string $method,
        array $allowed,
        string $format = null
    ): HttpException {
        $format = $format ?? 'Cannot access resource `%s` using method `%s`';
        $message = sprintf($format, $path, $method);
        $error = new HttpException($message, 405);
        $error->allowed = $allowed;
        return $error;
    }

    /**
     * @param string $path
     * @param string|null $format
     * @return static
     */
    public static function badRequest(string $path, string $format = null): HttpException
    {
        $format = $format ?? 'Cannot parse the request: %s';
        return new HttpException(sprintf(
            $format,
            $path
        ), 400);
    }
}
