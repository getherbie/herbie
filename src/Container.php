<?php

declare(strict_types=1);

namespace herbie;

use Psr\Container\ContainerInterface;
use Psr\Log\InvalidArgumentException;

final class Container implements ContainerInterface
{
    private array $values = [];

    private array $frozen = [];

    /**
     * @return mixed
     */
    public function get(string $id)
    {
        return $this->offsetGet($id);
    }

    public function has(string $id): bool
    {
        return $this->offsetExists($id);
    }

    /**
     * @param mixed $service
     */
    public function set(string $id, $service): void
    {
        $this->offsetUnset($id);
        $this->offsetSet($id, $service);
    }

    private function offsetExists(string $offset): bool
    {
        return isset($this->frozen[$offset]) || isset($this->values[$offset]);
    }

    /**
     * @return mixed
     */
    private function offsetGet(string $offset)
    {
        if (!$this->offsetExists($offset)) {
            $message = sprintf('Object "%s" is not stored in container', $offset);
            throw new InvalidArgumentException($message);
        }
        if (isset($this->frozen[$offset])) {
            return $this->frozen[$offset];
        }
        if (is_callable($this->values[$offset])) {
            if (!isset($this->frozen[$offset])) {
                $this->frozen[$offset] = $this->values[$offset]($this);
            }
            return $this->frozen[$offset];
        }
        if (!isset($this->frozen[$offset])) {
            $this->frozen[$offset] = $this->values[$offset];
        }
        return $this->frozen[$offset];
    }

    /**
     * @param mixed $value
     */
    private function offsetSet(string $offset, $value): void
    {
        $this->values[$offset] = $value;
    }

    private function offsetUnset(string $offset): void
    {
        unset($this->frozen[$offset]);
        unset($this->values[$offset]);
    }
}
