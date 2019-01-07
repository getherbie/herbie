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
use Herbie\Repository\DataRepositoryInterface;

/**
 * Stores the site.
 */
class Site
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var DataRepositoryInterface
     */
    private $dataRepository;

    /**
     * @var MenuList
     */
    private $menuList;

    /**
     * @var MenuTree
     */
    private $menuTree;

    /**
     * @var RootPath
     */
    private $menuRootPath;

    /**
     * Site constructor.
     * @param Config $config
     * @param DataRepositoryInterface $dataRepository
     * @param MenuList $menuList
     * @param MenuTree $menuTree
     * @param RootPath $menuRootPath
     */
    public function __construct(
        Config $config,
        DataRepositoryInterface $dataRepository,
        MenuList $menuList,
        MenuTree $menuTree,
        RootPath $menuRootPath
    ) {
        $this->config = $config;
        $this->dataRepository = $dataRepository;
        $this->menuList = $menuList;
        $this->menuTree = $menuTree;
        $this->menuRootPath = $menuRootPath;
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
        return $this->dataRepository->loadAll();
    }

    /**
     * @return MenuList
     */
    public function getMenu(): MenuList
    {
        return $this->menuList;
    }

    /**
     * @return MenuTree
     */
    public function getTree(): MenuTree
    {
        return $this->menuTree;
    }

    /**
     * @return MenuTree
     */
    public function getPageTree(): MenuTree
    {
        return $this->menuTree;
    }

    /**
     * @return RootPath
     */
    public function getRootPath(): RootPath
    {
        return $this->menuRootPath;
    }

    /**
     * @return string
     */
    public function getModified(): string
    {
        $lastModified = 0;
        foreach ($this->menuList as $item) {
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
        return $this->config->get('language');
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->config->get('locale');
    }

    /**
     * @return string
     */
    public function getCharset(): string
    {
        return $this->config->get('charset');
    }
}
