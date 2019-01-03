<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie;

use Herbie\Menu\MenuList;
use Herbie\Menu\MenuTree;
use Herbie\Menu\RootPath;

/**
 * Stores the site.
 */
class Site
{
    /**
     * @var Application
     */
    protected $herbie;

    /**
     * Site constructor.
     * @param Application $herbie
     */
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
        return $this->herbie->getDataRepository()->loadAll();
    }

    /**
     * @return MenuList
     */
    public function getMenu(): MenuList
    {
        return $this->herbie->getMenuList();
    }

    /**
     * @return MenuTree
     */
    public function getTree(): MenuTree
    {
        return $this->herbie->getMenuTree();
    }

    /**
     * @return MenuTree
     */
    public function getPageTree(): MenuTree
    {
        return $this->herbie->getMenuTree();
    }

    /**
     * @return RootPath
     */
    public function getRootPath(): RootPath
    {
        return $this->herbie->getMenuRootPath();
    }

    /**
     * @return string
     */
    public function getModified(): string
    {
        $lastModified = 0;
        foreach ($this->herbie->getMenuList() as $item) {
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
