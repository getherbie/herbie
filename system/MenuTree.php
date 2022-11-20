<?php

declare(strict_types=1);

namespace herbie;

final class MenuTree extends AbstractNode
{
    public function __toString(): string
    {
        $menuItem = $this->getMenuItem();
        return (string) $menuItem->title;
    }

    /**
     * @return mixed
     */
    public function getMenuItem()
    {
        return $this->getValue();
    }

    public function findByRoute(string $route): ?MenuTree
    {
        if (empty($route)) {
            /** @var MenuTree|null */
            return $this->root();
        }
        $menuItem = $this->getMenuItem();
        if (isset($menuItem) && ($menuItem->route === $route)) {
            return $this;
        }
        foreach ($this->getChildren() as $child) {
            $node = $child->findByRoute($route);
            if ($node) {
                return $node;
            }
        }
        return null;
    }

    /**
     * @param mixed $value
     * @return MenuTree|bool
     */
    public function findBy(string $name, $value)
    {
        $menuItem = $this->getMenuItem();
        if (isset($menuItem) && ($menuItem->$name === $value)) {
            return $this;
        }
        foreach ($this->getChildren() as $child) {
            /** @var MenuTree $child */
            $node = $child->findBy($name, $value);
            if ($node) {
                return $node;
            }
        }
        return false;
    }
}
