<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-01-18
 * Time: 06:53
 */

declare(strict_types=1);

namespace Herbie\Menu;

class MenuFactory
{

    public function newMenuItem(array $data = [])
    {
        return new MenuItem($data);
    }

    public function newMenuList(array $items = [])
    {
        return new MenuList($items);
    }
}
