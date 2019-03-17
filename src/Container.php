<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

use Psr\Container\ContainerInterface;
use Psr\Log\InvalidArgumentException;

class Container implements ContainerInterface
{
    /**
     * @var array
     */
    private $values = [];

    /**
     * @var array
     */
    private $frozen = [];

    /**
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        return $this->offsetGet($id);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has($id): bool
    {
        return $this->offsetExists($id);
    }

    /**
     * @param string $id
     * @param mixed $service
     */
    public function set($id, $service): void
    {
        $this->offsetUnset($id);
        $this->offsetSet($id, $service);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    private function offsetExists($offset): bool
    {
        return isset($this->frozen[$offset]) || isset($this->values[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    private function offsetGet($offset)
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
     * @param mixed $offset
     * @param mixed $value
     */
    private function offsetSet($offset, $value): void
    {
        $this->values[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    private function offsetUnset($offset): void
    {
        unset($this->frozen[$offset]);
        unset($this->values[$offset]);
    }
}
