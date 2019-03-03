<?php

namespace herbie\sysplugins\adminpanel\actions\tools;

use herbie\Alias;
use function herbie\empty_folder;
use herbie\sysplugins\adminpanel\classes\Payload;
use herbie\sysplugins\adminpanel\classes\PayloadFactory;
use Psr\Http\Message\ServerRequestInterface;

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
     * @var ServerRequestInterface
     */
    private $request;

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
        empty_folder($path);
        return $payload
            ->setStatus(Payload::SUCCESS)
            ->setOutput(['count' => 0]);
    }
}
