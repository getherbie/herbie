<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-02-10
 * Time: 11:26
 */

namespace herbie\sysplugins\adminpanel\actions\media;

use Herbie\Alias;
use herbie\sysplugins\adminpanel\classes\MediaUserInput;

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
     * UploadFileAction constructor.
     * @param Alias $alias
     * @param MediaUserInput $userInput
     */
    public function __construct(Alias $alias, MediaUserInput $userInput)
    {
        $this->alias = $alias;
        $this->userInput = $userInput;
    }

    public function __invoke()
    {
        $currentDir = $this->userInput->getCurrentDir();
        $uploadFile = $this->userInput->getUploadFile();
        $clientFileName = $this->userInput->sanitizeClientFilename($uploadFile->getClientFilename());
        $targetPath = sprintf('%s/%s/%s', $this->alias->get('@media'), $currentDir, $clientFileName);

        $uploadFile->moveTo($targetPath);

        return [
            'type' => 'file',
            'path' => $currentDir . '/' . $clientFileName,
            'name' => $clientFileName,
            'size' => $uploadFile->getSize()
        ];
    }
}
