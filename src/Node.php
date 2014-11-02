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

class Node implements \IteratorAggregate
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @var Node
     */
    private $parent;

    /**
     * @var array[Node]
     */
    private $children;

    public function __construct($value = null)
    {
        $this->value = $value;
        $this->parent = null;
        $this->children = [];
    }

    public function getIterator() {
        return new \ArrayIterator($this->children);
    }

    public function __destruct()
    {}

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
    public function isRoot()
    {
        return null === $this->parent;
    }

    /**
     * @param \Herbie\Node $parent
     */
    public function setParent(Node $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return \Herbie\Node
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return bool
     */
    public function hasParent()
    {
        return null !== $this->parent;
    }

    /**
     * @param \Herbie\Node $child
     */
    public function addChild(Node $child)
    {
        $child->setParent($this);
        array_push($this->children, $child);
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return !empty($this->children);
    }

    /**
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return \Herbie\Node
     */
    public function root()
    {
        if (is_null($this->parent)) {
            return $this;
        } else {
            return $this->parent->root();
        }
    }

    /*
     * JUST FOR TESTING PURPOSES!
     */

    /**
     * @param string $sval
     * @param string $skey
     * @return boolean|\Herbie\Node
     */
    public function find($sval, $skey)
    {
        if ($this->$skey == $sval) {
            return $this;
        }
        foreach ($this->getChildren() as $child) {
            $node = $child->find($sval, $skey);
            if ($node) {
                return $node;
            }
        }
        return false;
    }

    /**
     * @param string $pre
     * @param string $outfunc
     */
    public function render($pre = '', $outfunc = 'renderItem')
    {
        $this->$outfunc($pre);

        $pre = strtr($pre, '+-/\\', '|   ');

        $numChildren = count($this->children);

        for ($i = 0; $i < $numChildren; $i++) {
            switch ($i) {
                # Der erste Knoten
                case 0:
                    echo $pre . "|\n";
                    # Der erste und letzte Knoten
                    if ($i == ($numChildren - 1)) {
                        $this->children[$i]->render($pre . '\\--', $outfunc);
                        echo $pre . "\n";
                    } else {
                        $this->children[$i]->render($pre . '+--', $outfunc);
                    }
                    break;
                # Der letzte Knoten
                case ($numChildren - 1):
                    $this->children[$i]->render($pre . '\\--', $outfunc);
                    echo $pre . "\n";
                    $pre = strtr($pre, '|', ' ');
                    break;
                default:
                    $this->children[$i]->render($pre . '+--', $outfunc);
            }
        }
    }

    public function renderItem($pstr = '')
    {

    }

}
