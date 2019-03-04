<?php

namespace herbie\sysplugins\adminpanel\actions\tools;

use herbie\Alias;
use herbie\sysplugins\adminpanel\classes\Payload;
use herbie\sysplugins\adminpanel\classes\PayloadFactory;
use herbie\sysplugins\adminpanel\classes\UserInput;
use herbie\Yaml;

class FormatConfigAction
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
        $success = $this->formatConfig($path);
        if ($success) {
            return $payload
                ->setStatus(Payload::SUCCESS);
        }
        return $payload
            ->setStatus(Payload::ERROR)
            ->setMessages(['message' => 'Could not reformat file']);
    }

    /**
     * @param string $path
     * @return bool
     */
    private function formatConfig(string $path): bool
    {
        $parsed = Yaml::parseFile($path);
        $content = Yaml::dump($parsed);
        if (!file_put_contents($path, $content)) {
            return false;
        }
        return true;
    }
}
