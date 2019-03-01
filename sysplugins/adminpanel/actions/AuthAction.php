<?php

namespace herbie\sysplugins\adminpanel\actions;

use Firebase\JWT\JWT;
use herbie\sysplugins\adminpanel\classes\Payload;
use herbie\sysplugins\adminpanel\classes\PayloadFactory;
use Psr\Http\Message\ServerRequestInterface;

class AuthAction
{
    /**
     * @var ServerRequestInterface
     */
    private $request;
    /**
     * @var PayloadFactory
     */
    private $payloadFactory;

    /**
     * AuthAction constructor.
     * @param PayloadFactory $payloadFactory
     * @param ServerRequestInterface $request
     */
    public function __construct(PayloadFactory $payloadFactory, ServerRequestInterface $request)
    {
        $this->request = $request;
        $this->payloadFactory = $payloadFactory;
    }

    /**
     * @return Payload
     */
    public function __invoke(): Payload
    {
        $payload = $this->payloadFactory->newInstance();
        $input = json_decode($this->request->getBody(), true);

        if (($input['username'] == 'demo') && ($input['password'] == 'demo')) {
            setcookie('HERBIE_FRONTEND_PANEL', 1, 0, '/');
            $token = $this->generateToken();
            return $payload
                ->setStatus(Payload::AUTHENTICATED)
                ->setOutput(['token' => $token]);
        }

        return $payload
            ->setStatus(Payload::NOT_VALID)
            ->setOutput(['message' => 'Invalid username or password']);
    }

    /**
     * @return string
     */
    private function generateToken()
    {
        $payload = [
            'iss' => 'org.getherbie',
            'iat' => time(),
            'nbf' => time(),
            //'exp' => time() + (60*60*24),
            'user' => 'demo'
        ];
        $key = 'my_secret_key';
        $jwt = JWT::encode($payload, $key, 'HS256');
        return $jwt;
    }
}
