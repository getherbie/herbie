<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Plugin implements EventSubscriberInterface
{

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        $methods = get_class_methods(get_called_class());

        $list = array();
        foreach ($methods as $method) {
            if (strpos($method, 'on') === 0) {
                $list[$method] = [$method, 0];
            }
        }

        return $list;
    }
}
