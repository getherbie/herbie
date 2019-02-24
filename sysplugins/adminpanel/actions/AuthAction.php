<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-02-10
 * Time: 10:54
 */

namespace herbie\sysplugins\adminpanel\actions;

use Psr\Http\Message\ServerRequestInterface;

class AuthAction
{
    /**
     * @var ServerRequestInterface
     */
    private $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function __invoke()
    {
        $input = json_decode($this->request->getBody(), true);
        return ['token' => 'xxx'];
    }
}
