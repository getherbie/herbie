<?php

declare(strict_types=1);

namespace herbie;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use OutOfBoundsException;

/**
 * @implements ArrayAccess<string|int, string>
 * @implements IteratorAggregate<string|int, string>
 */
final class PageSegments implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * @param array<string|int, string> $items
     */
    public function __construct(private array $items = []) {}

    /**
     * @param array<string|int, string> $items
     */
    public static function fromArray(array $items): self
    {
        return new self($items);
    }

    /**
     * @return array<string|int, string>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return array<string|int, string>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    public function has(string|int $id): bool
    {
        return array_key_exists($id, $this->items);
    }

    public function get(string|int $id, string $default = ''): string
    {
        if (!$this->has($id)) {
            return $default;
        }
        return (string)$this->items[$id];
    }

    public function set(string|int $id, string $content): void
    {
        $this->items[$id] = $content;
    }

    public function remove(string|int $id): void
    {
        unset($this->items[$id]);
    }

    public function getDefault(string $default = ''): string
    {
        return $this->get('default', $default);
    }

    public function setDefault(string $content): void
    {
        $this->set('default', $content);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /** @return ArrayIterator<string|int, string> */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        if (!is_string($offset) && !is_int($offset)) {
            return false;
        }
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): string
    {
        if (!is_string($offset) && !is_int($offset)) {
            throw new OutOfBoundsException('Segment id must be of type string or int.');
        }
        if (!$this->has($offset)) {
            throw new OutOfBoundsException(sprintf('Segment "%s" does not exist.', (string)$offset));
        }
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = (string)$value;
            return;
        }
        if (!is_string($offset) && !is_int($offset)) {
            throw new OutOfBoundsException('Segment id must be of type string or int.');
        }
        $this->set($offset, (string)$value);
    }

    public function offsetUnset(mixed $offset): void
    {
        if (!is_string($offset) && !is_int($offset)) {
            return;
        }
        $this->remove($offset);
    }
}
