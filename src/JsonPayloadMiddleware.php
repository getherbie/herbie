<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JsonPayloadMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if (stripos($contentType, 'application/json') === 0) {
            $json = (string)$request->getBody();
            $data = json_decode($json, true);
            $code = json_last_error();
            if ($code !== JSON_ERROR_NONE) {
                // This can be modified for PHP 7.3 when it is stable:
                // https://ayesh.me/Upgrade-PHP-7.3#json-exceptions
                throw new \Exception(sprintf('JSON: %s', json_last_error_msg()), $code);
            }
            $request = $request->withParsedBody($data);
        }

        return $next->handle($request);
    }
}
