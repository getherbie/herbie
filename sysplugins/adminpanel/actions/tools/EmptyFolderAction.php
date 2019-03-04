<?php

namespace herbie\sysplugins\adminpanel\actions\tools;

use herbie\Alias;
use function herbie\empty_folder;
use herbie\sysplugins\adminpanel\classes\Payload;
use herbie\sysplugins\adminpanel\classes\PayloadFactory;
use herbie\sysplugins\adminpanel\classes\UserInput;

class EmptyFolderAction
{
    /**
     * @var Alias
     */
    private $alias;

    /**
     * @var PayloadFactory
     */
    private $payloadFactory;

    /**
     * @var UserInput
     */
    private $userInput;

    /**
     * IndexAction constructor.
     * @param Alias $alias
     * @param PayloadFactory $payloadFactory
     * @param UserInput $userInput
     */
    public function __construct(Alias $alias, PayloadFactory $payloadFactory, UserInput $userInput)
    {
        $this->alias = $alias;
        $this->payloadFactory = $payloadFactory;
        $this->userInput = $userInput;
    }

    /**
     * @return Payload
     */
    public function __invoke(): Payload
    {
        $payload = $this->payloadFactory->newInstance();
        $alias = $this->userInput->getBodyParam('alias', FILTER_SANITIZE_STRING);
        $path = $this->alias->get(trim($alias));
        empty_folder($path);
        return $payload
            ->setStatus(Payload::SUCCESS)
            ->setOutput(['count' => 0]);
    }

}
