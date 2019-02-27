<?php

namespace herbie\sysplugins\adminpanel\actions\data;

use herbie\sysplugins\adminpanel\classes\Payload;
use herbie\sysplugins\adminpanel\classes\PayloadFactory;

class IndexAction
{
    /**
     * @var PayloadFactory
     */
    private $payloadFactory;

    /**
     * IndexAction constructor.
     * @param PayloadFactory $payloadFactory
     */
    public function __construct(PayloadFactory $payloadFactory)
    {
        $this->payloadFactory = $payloadFactory;
    }

    /**
     * @return Payload
     */
    public function __invoke(): Payload
    {
        $payload = $this->payloadFactory->newInstance();
        return $payload
            ->setStatus(Payload::FOUND)
            ->setOutput([]);
    }
}
