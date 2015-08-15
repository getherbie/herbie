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

/**
 * Stores the site.
 */
class Site
{

    /**
     * @return string
     */
    public function getTime()
    {
        return date('c');
    }

    /**
     * @return array
     */
    public function getData()
    {
        return Application::getService('DataArray');
    }

    /**
     * @return Menu\Page\Collection
     */
    public function getMenu()
    {
        return Application::getService('Menu\Page\Collection');
    }

    /**
     * @return Menu\Page\Node
     */
    public function getTree()
    {
        return Application::getService('Menu\Page\Node');
    }

    /**
     * @return Menu\Page\Node
     */
    public function getPageTree()
    {
        return Application::getService('Menu\Page\Node');
    }

    /**
     * @return Menu\Post\Collection
     */
    public function getPosts()
    {
        return Application::getService('Menu\Post\Collection');
    }

    /**
     * @return Menu\Page\RootPath
     */
    public function getRootPath()
    {
        return Application::getService('Menu\Page\RootPath');
    }

    /**
     * @return string
     */
    public function getModified()
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
    public function getLanguage()
    {
        return Application::getService('Config')->get('language');
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return Application::getService('Config')->get('locale');
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return Application::getService('Config')->get('charset');
    }
}
