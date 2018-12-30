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
    protected $herbie;

    public function __construct(Application $herbie)
    {
        $this->herbie = $herbie;
    }

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
        return $this->herbie->getDataArray();
    }

    /**
     * @return \Herbie\Menu\Page\Collection
     */
    public function getMenu(): \Herbie\Menu\Page\Collection
    {
        return $this->herbie->getMenuPageCollection();
    }

    /**
     * @return Node
     */
    public function getTree(): Node
    {
        return $this->herbie->getMenuPageNode();
    }

    /**
     * @return Node
     */
    public function getPageTree(): Node
    {
        return $this->herbie->getMenuPageNode();
    }

    /**
     * @return Menu\Post\Collection
     */
    public function getPosts(): \Herbie\Menu\Post\Collection
    {
        return $this->herbie->getMenuPostCollection();
    }

    /**
     * @return RootPath
     */
    public function getRootPath(): RootPath
    {
        return $this->herbie->getMenuPageRootPath();
    }

    /**
     * @return string
     */
    public function getModified(): string
    {
        $lastModified = 0;
        foreach ($this->herbie->getMenuPageCollection() as $item) {
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
        return $this->herbie->getConfig()->get('language');
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->herbie->getConfig()->get('locale');
    }

    /**
     * @return string
     */
    public function getCharset(): string
    {
        return $this->herbie->getConfig()->get('charset');
    }
}
