<?php

namespace herbie\sysplugins\adminpanel\actions\media;

use herbie\Alias;
use herbie\sysplugins\adminpanel\classes\MediaUserInput;
use herbie\sysplugins\adminpanel\classes\Payload;
use herbie\sysplugins\adminpanel\classes\PayloadFactory;
use Psr\Http\Message\ServerRequestInterface;

class DeleteFileAction
{
    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var Alias
     */
    private $alias;

    /**
     * @var MediaUserInput
     */
    private $userInput;

    /**
     * @var PayloadFactory
     */
    private $payloadFactory;

    /**
     * DeleteFileAction constructor.
     * @param Alias $alias
     * @param MediaUserInput $userInput
     * @param PayloadFactory $payloadFactory
     * @param ServerRequestInterface $request
     */
    public function __construct(Alias $alias, MediaUserInput $userInput, PayloadFactory $payloadFactory, ServerRequestInterface $request)
    {
        $this->alias = $alias;
        $this->payloadFactory = $payloadFactory;
        $this->userInput = $userInput;
        $this->request = $request;
    }

    /**
     * @return Payload
     */
    public function __invoke(): Payload
    {
        $payload = $this->payloadFactory->newInstance();

        $input = $this->request->getParsedBody();
        $file = $input['file'] ?? '';
        $path = $this->alias->get('@media/' . $file);

        if (!is_file($path) || !is_writable($path)) {
            return $payload
                ->setStatus(Payload::NOT_VALID)
                ->setOutput(['message' => 'Kein gueltiger pfad: ' . $path]);
        }

        $success = unlink($path);

        if (!$success) {
            return $payload
                ->setStatus(Payload::ERROR)
                ->setOutput(['message' => 'Could not delete file']);
        }

        return $payload
            ->setStatus(Payload::SUCCESS);
    }
}
