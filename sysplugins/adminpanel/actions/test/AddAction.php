<?php

namespace herbie\sysplugins\adminpanel\actions\test;

use herbie\sysplugins\adminpanel\classes\Payload;
use herbie\sysplugins\adminpanel\classes\PayloadFactory;
use Psr\Http\Message\ServerRequestInterface;

class AddAction
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
     * AddAction constructor.
     * @param ServerRequestInterface $request
     * @param PayloadFactory $payloadFactory
     */
    public function __construct(ServerRequestInterface $request, PayloadFactory $payloadFactory)
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
        $name = $input['name'] ?? '';
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
