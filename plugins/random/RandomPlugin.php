<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\plugin\random;

use Herbie;

class RandomPlugin extends Herbie\Plugin
{

    public function onPageLoaded(Herbie\Event $event)
    {
        $page = $event['page'];
        if(!empty($page->random)) {
            $excludeRoutes = empty($page->random['excludes']) ? [] : $page->random['excludes'];
            do {
                $menuItem = $this->app['menu']->getRandom();
                $invalid = ($menuItem->hidden == true)
                    || ($menuItem->path == $page->path)
                    || in_array($menuItem->route, $excludeRoutes);
            } while ($invalid);
            if(isset($menuItem)) {
                $page->load($menuItem->getPath());
            }
        }
    }

}
