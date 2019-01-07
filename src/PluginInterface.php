<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-01-07
 * Time: 19:53
 */

namespace Herbie;

use Zend\EventManager\EventManagerInterface;

interface PluginInterface
{
    public function attach(EventManagerInterface $events, $priority = 1);

}
