<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

use Herbie\Menu\Page\RootPath;

/**
 * Stores the site.
 */
class Site
{

    /**
     * @return string
     */
    public function getTime(): string
    {
        return date('c');
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return Application::getService('DataArray');
    }

    /**
     * @return \Herbie\Menu\Page\Collection
     */
    public function getMenu(): \Herbie\Menu\Page\Collection
    {
        return Application::getService('Menu\Page\Collection');
    }

    /**
     * @return Node
     */
    public function getTree(): Node
    {
        return Application::getService('Menu\Page\Node');
    }

    /**
     * @return Node
     */
    public function getPageTree(): Node
    {
        return Application::getService('Menu\Page\Node');
    }

    /**
     * @return Menu\Post\Collection
     */
    public function getPosts(): \Herbie\Menu\Post\Collection
    {
        return Application::getService('Menu\Post\Collection');
    }

    /**
     * @return RootPath
     */
    public function getRootPath(): RootPath
    {
        return Application::getService('Menu\Page\RootPath');
    }

    /**
     * @return string
     */
    public function getModified(): string
    {
        $lastModified = 0;
        foreach (Application::getService('Menu\Page\Collection') as $item) {
            $modified = strtotime($item->getModified());
            if ($modified > $lastModified) {
                $lastModified = $modified;
            }
        }
        return date('c', $lastModified);
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return Application::getService('Config')->get('language');
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return Application::getService('Config')->get('locale');
    }

    /**
     * @return string
     */
    public function getCharset(): string
    {
        return Application::getService('Config')->get('charset');
    }
}
