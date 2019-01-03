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

class Node implements \IteratorAggregate
{

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var Node
     */
    protected $parent;

    /**
     * @var Node[]
     */
    protected $children;

    /**
     * @param mixed $value
     */
    public function __construct($value = null)
    {
        $this->value = $value;
        $this->parent = null;
        $this->children = [];
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->children);
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isRoot(): bool
    {
        return null === $this->parent;
    }

    /**
     * @param Node $parent
     */
    public function setParent(Node $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return Node|null
     */
    public function getParent(): Node
    {
        return $this->parent;
    }

    /**
     * @return bool
     */
    public function hasParent(): bool
    {
        return null !== $this->parent;
    }

    /**
     * @param Node $child
     */
    public function addChild(Node $child): void
    {
        $child->setParent($this);
        $this->children[] = $child;
    }

    /**
     * @return bool
     */
    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    /**
     * @return Node[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return Node
     */
    public function root(): Node
    {
        if (is_null($this->parent)) {
            return $this;
        } else {
            return $this->parent->root();
        }
    }
}
