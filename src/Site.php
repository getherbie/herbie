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
use Herbie\Menu\MenuTrail;
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
     * @var MenuTrail
     */
    private $menuTrail;

    /**
     * Site constructor.
     * @param Config $config
     * @param DataRepositoryInterface $dataRepository
     * @param MenuList $menuList
     * @param MenuTree $menuTree
     * @param MenuTrail $menuTrail
     */
    public function __construct(
        Config $config,
        DataRepositoryInterface $dataRepository,
        MenuList $menuList,
        MenuTree $menuTree,
        MenuTrail $menuTrail
    ) {
        $this->config = $config;
        $this->dataRepository = $dataRepository;
        $this->menuList = $menuList;
        $this->menuTree = $menuTree;
        $this->menuTrail = $menuTrail;
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
     * TODO fix method name
     */
    public function getMenu(): MenuList
    {
        return $this->menuList;
    }

    /**
     * @return MenuTree
     * TODO fix method name
     */
    public function getTree(): MenuTree
    {
        return $this->menuTree;
    }

    /**
     * @return MenuTree
     * TODO fix method name
     */
    public function getPageTree(): MenuTree
    {
        return $this->menuTree;
    }

    /**
     * @return MenuTrail
     * TODO fix method name
     */
    public function getRootPath(): MenuTrail
    {
        return $this->menuTrail;
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
