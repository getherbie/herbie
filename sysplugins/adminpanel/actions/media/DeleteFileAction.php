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

    public function __construct(ServerRequestInterface $request, Alias $alias, MediaUserInput $userInput)
    {
        $this->request = $request;
        $this->alias = $alias;
        $this->userInput = $userInput;
    }

    public function __invoke()
    {
        $input = json_decode($this->request->getBody(), true);
        $file = $input['file'] ?? '';

        $mediaRootPath = $this->alias->get('@media/');
        $deleteFile = $this->userInput->getDeleteFile();

        $path = $this->alias->get('@media/' . $file);

        if (!is_file($path) || !is_writable($path)) {
            throw new \Exception('Kein gueltiger pfad: ' . $path);
        }

        $success = unlink($path);

        if (!$success) {
            throw new \Exception('Could not delete file');
        }

        return [true];
    }
}
