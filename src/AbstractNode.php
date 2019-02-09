<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie;

abstract class AbstractNode implements \IteratorAggregate
{

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var AbstractNode
     */
    private $parent;

    /**
     * @var AbstractNode[]
     */
    private $children;

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
     * @param AbstractNode $parent
     */
    public function setParent(AbstractNode $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return AbstractNode|null
     */
    public function getParent(): AbstractNode
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
     * @param AbstractNode $child
     */
    public function addChild(AbstractNode $child): void
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
     * @return AbstractNode[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return AbstractNode
     */
    public function root(): AbstractNode
    {
        if (is_null($this->parent)) {
            return $this;
        } else {
            return $this->parent->root();
        }
    }
}
