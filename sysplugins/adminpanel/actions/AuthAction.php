<?php

namespace herbie\sysplugins\adminpanel\actions;

use Firebase\JWT\JWT;
use herbie\sysplugins\adminpanel\classes\Payload;
use herbie\sysplugins\adminpanel\classes\PayloadFactory;
use herbie\sysplugins\adminpanel\classes\UserInput;

class AuthAction
{
    /**
     * @var PayloadFactory
     */
    private $payloadFactory;

    /**
     * @var UserInput
     */
    private $userInput;

    /**
     * AuthAction constructor.
     * @param PayloadFactory $payloadFactory
     * @param UserInput $userInput
     */
    public function __construct(PayloadFactory $payloadFactory, UserInput $userInput)
    {
        $this->payloadFactory = $payloadFactory;
        $this->userInput = $userInput;
    }

    /**
     * @return Payload
     */
    public function __invoke(): Payload
    {
        $payload = $this->payloadFactory->newInstance();

        $username = $this->userInput->getBodyParam('username', FILTER_SANITIZE_STRING);
        $password = $this->userInput->getBodyParam('password', FILTER_SANITIZE_STRING);

        if (($username == 'demo') && ($password == 'demo')) {
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
