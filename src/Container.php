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

use Psr\Container\ContainerInterface;
use Psr\Log\InvalidArgumentException;

class Container implements \ArrayAccess, ContainerInterface
{
    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var array
     */
    protected $frozen = [];

    /**
     * @param string $id
     * @return mixed
     * @throws \Exception
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
    public function set($id, $service)
    {
        $this->offsetUnset($id);
        $this->offsetSet($id, $service);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->frozen[$offset]) || isset($this->values[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     * @throws \Exception
     */
    public function offsetGet($offset)
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
    public function offsetSet($offset, $value)
    {
        $this->values[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->frozen[$offset]);
        unset($this->values[$offset]);
    }
}
