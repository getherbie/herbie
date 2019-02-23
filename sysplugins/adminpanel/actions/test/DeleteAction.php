<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-02-10
 * Time: 10:54
 */

namespace herbie\sysplugins\adminpanel\actions\test;

class DeleteAction
{
    public function __invoke(int $id)
    {
        return json_encode(true);
    }
}
