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

class HttpBasicAuthMiddleware implements MiddlewareInterface
{
    /**
     * @var array
     */
    private $users;

    /**
     * HttpBasicAuthMiddleware constructor.
     * @param array $users
     */
    public function __construct(array $users)
    {
        $this->users = $users;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $login = $this->login($request);

        if (empty($login)) {
            return HttpFactory::instance()
                ->createResponse(401, 'Unauthorized')
                ->withHeader('WWW-Authenticate', 'Basic realm="Test"');
        }

        $response = $handler->handle($request);

        return $response;
    }

    /**
     * Check the user credentials and return the username or false.
     *
     * @param ServerRequestInterface $request
     * @return bool|mixed
     */
    private function login(ServerRequestInterface $request)
    {
        //Check header
        $authorization = $this->parseHeader($request->getHeaderLine('Authorization'));

        if (!$authorization) {
            return false;
        }
        //Check the user
        if (!isset($this->users[$authorization['username']])) {
            return false;
        }
        if ($this->users[$authorization['username']] !== $authorization['password']) {
            return false;
        }
        return $authorization['username'];
    }

    /**
     * Parses the authorization header for a basic authentication.
     *
     * @param string $header
     * @return array|bool
     */
    private function parseHeader(string $header)
    {
        if (strpos($header, 'Basic') !== 0) {
            return false;
        }
        $header = explode(':', base64_decode(substr($header, 6)), 2);
        return [
            'username' => $header[0],
            'password' => isset($header[1]) ? $header[1] : null,
        ];
    }
}
