<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-02-10
 * Time: 11:26
 */

namespace herbie\sysplugins\adminpanel\actions\media;

use herbie\Alias;
use herbie\sysplugins\adminpanel\classes\MediaUserInput;

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
     * UploadFileAction constructor.
     * @param Alias $alias
     * @param MediaUserInput $userInput
     */
    public function __construct(Alias $alias, MediaUserInput $userInput)
    {
        $this->alias = $alias;
        $this->userInput = $userInput;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function __invoke(): array
    {
        $currentDir = $this->userInput->getCurrentDir();
        $folderName = $this->userInput->getFolderName();

        if (strlen($folderName) === 0) {
            throw new \Exception("Verzeichnisname ist leer");
        }

        $relPath = $currentDir . '/' . $folderName;
        $absPath = $this->alias->get('@media/' . $relPath);

        if (is_dir($absPath)) {
            throw new \Exception("Verzeichnis {$folderName} existiert schon");
        }

        $success = @mkdir($absPath);

        if (!$success) {
            throw new \Exception("Verzeichnis {$folderName} konnte nicht erstellt werden");
        }

        return [
            'type' => 'dir',
            'path' => $relPath,
            'name' => basename($folderName),
            'size' => 0,
            'ext' => ''
        ];
    }

}
