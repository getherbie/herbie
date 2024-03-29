<?php

declare(strict_types=1);

namespace herbie;

use ArrayIterator;
use IteratorAggregate;

abstract class AbstractNode implements IteratorAggregate
{
    /**
     * @var mixed
     */
    private $value;

    private ?AbstractNode $parent;

    /**
     * @var AbstractNode[]
     */
    private array $children;

    /**
     * @param mixed $value
     */
    public function __construct($value = null)
    {
        $this->value = $value;
        $this->parent = null;
        $this->children = [];
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->children);
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    public function isRoot(): bool
    {
        return null === $this->parent;
    }

    public function getParent(): ?AbstractNode
    {
        return $this->parent;
    }

    public function setParent(AbstractNode $parent): void
    {
        $this->parent = $parent;
    }

    public function hasParent(): bool
    {
        return null !== $this->parent;
    }

    public function addChild(AbstractNode $child): void
    {
        $child->setParent($this);
        $this->children[] = $child;
    }

    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function root(): AbstractNode
    {
        if ($this->parent === null) {
            return $this;
        } else {
            return $this->parent->root();
        }
    }
}
