<?php

namespace herbie\sysplugins\adminpanel\actions\media;

use herbie\Alias;
use herbie\sysplugins\adminpanel\classes\MediaUserInput;
use herbie\sysplugins\adminpanel\classes\Payload;
use herbie\sysplugins\adminpanel\classes\PayloadFactory;

class UploadFileAction
{
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
     * UploadFileAction constructor.
     * @param Alias $alias
     * @param MediaUserInput $userInput
     * @param PayloadFactory $payloadFactory
     */
    public function __construct(Alias $alias, MediaUserInput $userInput, PayloadFactory $payloadFactory)
    {
        $this->alias = $alias;
        $this->userInput = $userInput;
        $this->payloadFactory = $payloadFactory;
    }

    /**
     * @return Payload
     */
    public function __invoke(): Payload
    {
        $payload = $this->payloadFactory->newInstance();

        try {
            $currentDir = $this->userInput->getCurrentDir();
            $uploadFile = $this->userInput->getUploadFile();
            $clientFileName = $this->userInput->sanitizeClientFilename($uploadFile->getClientFilename());
            $targetPath = sprintf('%s/%s/%s', $this->alias->get('@media'), $currentDir, $clientFileName);

            $uploadFile->moveTo($targetPath);

            return $payload
                ->setStatus(Payload::SUCCESS)
                ->setOutput([
                    'type' => 'file',
                    'path' => $currentDir . '/' . $clientFileName,
                    'name' => $clientFileName,
                    'size' => $uploadFile->getSize()
                ]);

        } catch (\Throwable $t) {
            return $payload
                ->setStatus(Payload::ERROR)
                ->setOutput($t);
        }
    }
}
