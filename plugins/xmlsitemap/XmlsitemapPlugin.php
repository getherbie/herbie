<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\plugin\xmlsitemap;

use Herbie;
use Herbie\Loader\FrontMatterLoader;
use Herbie\Menu\Page\Item;

class XmlsitemapPlugin extends Herbie\Plugin
{

    /**
     * @param Herbie\Event $event
     */
    public function onPluginsInitialized(Herbie\Event $event)
    {
        $alias = $this->config(
            'plugins.config.xmlsitemap.pages.sitemap',
            '@plugin/xmlsitemap/pages/sitemap.xml'
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
