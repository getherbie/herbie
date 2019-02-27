<?php

namespace herbie\sysplugins\adminpanel\actions\media;

use herbie\Alias;
use herbie\sysplugins\adminpanel\classes\MediaUserInput;
use herbie\sysplugins\adminpanel\classes\Payload;
use herbie\sysplugins\adminpanel\classes\PayloadFactory;

class AddFolderAction
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
            $folderName = $this->userInput->getFolderName();

            if (strlen($folderName) === 0) {
                return $payload
                    ->setStatus(Payload::NOT_VALID)
                    ->setOutput(['message' => 'Verzeichnisname ist leer']);
            }

            $relPath = $currentDir . '/' . $folderName;
            $absPath = $this->alias->get('@media/' . $relPath);

            if (is_dir($absPath)) {
                return $payload
                    ->setStatus(Payload::NOT_VALID)
                    ->setOutput(['message' => "Verzeichnis {$folderName} existiert schon"]);
            }

            $success = @mkdir($absPath);

            if (!$success) {
                return $payload
                    ->setStatus(Payload::ERROR)
                    ->setOutput(['message' => "Verzeichnis {$folderName} konnte nicht erstellt werden"]);
            }

            return $payload
                ->setStatus(Payload::SUCCESS)
                ->setOutput([
                    'type' => 'dir',
                    'path' => $relPath,
                    'name' => basename($folderName),
                    'size' => 0,
                    'ext' => ''
                ]);

        } catch (\Throwable $t) {
            return $payload
                ->setStatus(PayloadStatus::ERROR)
                ->setOutput($t->getMessage());
        }
    }

}
