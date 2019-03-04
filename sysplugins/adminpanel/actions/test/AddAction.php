<?php

namespace herbie\sysplugins\adminpanel\actions\test;

use herbie\sysplugins\adminpanel\classes\Payload;
use herbie\sysplugins\adminpanel\classes\PayloadFactory;
use herbie\sysplugins\adminpanel\classes\UserInput;
use Psr\Http\Message\ServerRequestInterface;

class AddAction
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
     * AddAction constructor.
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

        $name = $this->userInput->getBodyParam('name', FILTER_SANITIZE_STRING);
        $name = trim($name);

        if (empty($name)) {
            return $payload
                ->setStatus(Payload::NOT_VALID)
                ->setOutput(['message' => 'Name is required']);
        }

        return $payload
            ->setStatus(Payload::FOUND)
            ->setOutput(['name' => $name]);
    }
}
