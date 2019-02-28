<?php

namespace herbie\sysplugins\adminpanel\actions\media;

use herbie\Alias;
use herbie\sysplugins\adminpanel\classes\Payload;
use herbie\sysplugins\adminpanel\classes\PayloadFactory;
use Psr\Http\Message\ServerRequestInterface;

class DeleteFolderAction
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
     * DeleteFolderAction constructor.
     * @param Alias $alias
     * @param PayloadFactory $payloadFactory
     * @param ServerRequestInterface $request
     */
    public function __construct(Alias $alias, PayloadFactory $payloadFactory, ServerRequestInterface $request)
    {
        $this->alias = $alias;
        $this->payloadFactory = $payloadFactory;
        $this->request = $request;
    }

    /**
     * @return Payload
     */
    public function __invoke(): Payload
    {
        $payload = $this->payloadFactory->newInstance();

        $input = json_decode($this->request->getBody(), true);
        $file = $input['folder'] ?? '';
        $path = $this->alias->get('@media/' . $file);

        if (!is_dir($path) || !is_writable($path)) {
            return $payload
                ->setStatus(Payload::NOT_VALID)
                ->setOutput(['message' => 'Kein gueltiger pfad: ' . $file]);
        }

        $success = @rmdir($path);

        if (!$success) {
            return $payload
                ->setStatus(Payload::ERROR)
                ->setOutput(['message' => 'Could not delete folder: ' . $file]);
        }

        return $payload
            ->setStatus(Payload::SUCCESS);
    }
}
