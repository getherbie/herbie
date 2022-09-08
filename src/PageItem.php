<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

class PageItem implements \ArrayAccess
{
    use PageItemTrait;

    /**
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        return $this->__isset($offset);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->__set($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        throw new \BadMethodCallException('Unset is not supported');
    }
}
