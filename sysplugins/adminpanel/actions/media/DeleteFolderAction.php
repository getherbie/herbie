<?php

namespace herbie\sysplugins\adminpanel\actions\media;

use Herbie\Alias;
use Psr\Http\Message\ServerRequestInterface;

class DeleteFolderAction
{
    /**
     * @var ServerRequestInterface
     */
    private $request;
    /**
     * @var Alias
     */
    private $alias;

    public function __construct(ServerRequestInterface $request, Alias $alias)
    {
        $this->request = $request;
        $this->alias = $alias;
    }

    public function __invoke()
    {
        $input = json_decode($this->request->getBody(), true);
        $file = $input['folder'] ?? '';
        $path = $this->alias->get('@media/' . $file);

        if (!is_dir($path) || !is_writable($path)) {
            throw new \Exception('Kein gueltiger pfad: ' . $path);
        }

        $success = @rmdir($path);

        if (!$success) {
            throw new \Exception('Could not delete folder: ' . $path);
        }

        return [true];
    }
}
