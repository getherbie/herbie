<?php

namespace herbie\sysplugins\adminpanel\actions\media;

use Herbie\Alias;
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

    public function __construct(ServerRequestInterface $request, Alias $alias)
    {
        $this->request = $request;
        $this->alias = $alias;
    }

    public function __invoke()
    {
        $params = $this->request->getParsedBody();

        return __METHOD__;
    }
}
