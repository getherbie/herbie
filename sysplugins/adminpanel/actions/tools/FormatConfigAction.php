<?php

namespace herbie\sysplugins\adminpanel\actions\tools;

use herbie\Alias;
use herbie\sysplugins\adminpanel\classes\Payload;
use herbie\sysplugins\adminpanel\classes\PayloadFactory;
use herbie\Yaml;
use Psr\Http\Message\ServerRequestInterface;

class FormatConfigAction
{
    /**
     * @var PayloadFactory
     */
    private $payloadFactory;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var Alias
     */
    private $alias;

    /**
     * IndexAction constructor.
     * @param Alias $alias
     * @param PayloadFactory $payloadFactory
     * @param ServerRequestInterface $request
     */
    public function __construct(Alias $alias, PayloadFactory $payloadFactory, ServerRequestInterface $request)
    {
        $this->payloadFactory = $payloadFactory;
        $this->request = $request;
        $this->alias = $alias;
    }

    /**
     * @return Payload
     */
    public function __invoke(): Payload
    {
        $payload = $this->payloadFactory->newInstance();
        $input = json_decode($this->request->getBody(), true);
        $alias = strtolower(trim($input['alias']));
        $path = $this->alias->get($alias);
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
