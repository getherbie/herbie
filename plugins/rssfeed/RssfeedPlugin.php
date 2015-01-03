<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\plugin\rssfeed;

use Herbie;
use Herbie\Loader\FrontMatterLoader;
use Herbie\Menu\Page\Item;

class RssfeedPlugin extends Herbie\Plugin
{

    /**
     * @param Herbie\Event $event
     */
    public function onPluginsInitialized(Herbie\Event $event)
    {
        $alias = $this->config(
            'plugins.config.rssfeed.pages.feed',
            '@plugin/rssfeed/pages/feed.rss'
        );
        $path = $this->app['alias']->get($alias);
        $loader = new FrontMatterLoader();
        $item = $loader->load($path);
        $item['path'] = $alias;
        $event['app']['menu']->addItem(
            new Item($item)
        );
    }

}
