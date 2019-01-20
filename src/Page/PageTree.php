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

namespace Herbie\Page;

class PageTree extends \Herbie\Node
{
    /**
     * @return string
     */
    public function __toString(): string
    {
        $menuItem = $this->getMenuItem();
        return (string) $menuItem->title;
    }

    /**
     * @param PageList $menuList
     * @return PageTree
     */
    public static function buildTree(PageList $menuList): PageTree
    {
        $tree = new self();
        foreach ($menuList as $menuItem) {
            $route = $menuItem->getParentRoute();
            $node = $tree->findByRoute($route);
            if ($node) {
                $node->addChild(new self($menuItem));
            }
        }
        return $tree;
    }

    /**
     * @return mixed
     */
    public function getMenuItem()
    {
        return $this->getValue();
    }

    /**
     * @param string $route
     * @return PageTree|bool
     */
    public function findByRoute(string $route)
    {
        if (empty($route)) {
            return $this->root();
        }
        $menuItem = $this->getMenuItem();
        if (isset($menuItem) && ($menuItem->route == $route)) {
            return $this;
        }
        foreach ($this->getChildren() as $child) {
            $node = $child->findByRoute($route);
            if ($node) {
                return $node;
            }
        }
        return false;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return PageTree|bool
     */
    public function findBy(string $name, $value)
    {
        $menuItem = $this->getMenuItem();
        if (isset($menuItem) && ($menuItem->$name === $value)) {
            return $this;
        }
        foreach ($this->getChildren() as $child) {
            $node = $child->findBy($name, $value);
            if ($node) {
                return $node;
            }
        }
        return false;
    }
}
