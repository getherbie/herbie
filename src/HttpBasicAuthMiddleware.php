<?php

/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tebe\HttpFactory\HttpFactory;

final class HttpBasicAuthMiddleware implements MiddlewareInterface
{
    private array $users;

    /**
     * HttpBasicAuthMiddleware constructor.
     */
    public function __construct(array $users)
    {
        $this->users = $users;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $login = $this->login($request);

        if ($login === false) {
            return HttpFactory::instance()
                ->createResponse(401, 'Unauthorized')
                ->withHeader('WWW-Authenticate', 'Basic realm="Test"');
        }

        return $handler->handle($request);
    }

    /**
     * Check the user credentials and return the username or false.
     *
     * @return false|string
     */
    private function login(ServerRequestInterface $request)
    {
        // check header
        $authorization = $this->parseHeader($request->getHeaderLine('Authorization'));

        if ($authorization === false) {
            return false;
        }

        //Check the user
        $username = trim($authorization['username'] ?? '');
        $password = trim($authorization['password'] ?? '');

        if ((strlen($username) === 0) || (strlen($password) === 0)) {
            return false;
        }

        if (!isset($this->users[$username])) {
            return false;
        }

        if ($this->users[$username] !== $password) {
            return false;
        }

        return $username;
    }

    /**
     * Parses the authorization header for a basic authentication.
     *
     * @return array|false
     */
    private function parseHeader(string $header)
    {
        if (strpos($header, 'Basic') !== 0) {
            return false;
        }
        $header = explode(':', base64_decode(substr($header, 6)), 2);
        return [
            'username' => trim($header[0] ?? ''),
            'password' => trim($header[1] ?? ''),
        ];
    }
}
