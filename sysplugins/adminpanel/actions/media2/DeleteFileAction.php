<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-02-10
 * Time: 11:26
 */

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
