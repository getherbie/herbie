<?php

namespace herbie\sysplugins\adminpanel\actions\test;

use herbie\sysplugins\adminpanel\classes\Payload;
use herbie\sysplugins\adminpanel\classes\PayloadFactory;

class DeleteAction
{
    /**
     * @var PayloadFactory
     */
    private $payloadFactory;

    /**
     * DeleteAction constructor.
     * @param PayloadFactory $payloadFactory
     */
    public function __construct(PayloadFactory $payloadFactory)
    {
        $this->payloadFactory = $payloadFactory;
    }

    /**
     * @param int $id
     * @return Payload
     */
    public function __invoke(int $id): Payload
    {
        $payload = $this->payloadFactory->newInstance();
        return $payload
            ->setStatus(Payload::DELETED);
    }
}
