<?php

declare(strict_types=1);

namespace herbie;

final class PageItem implements \ArrayAccess
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
