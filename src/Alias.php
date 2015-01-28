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

class Alias
{

    /**
     * @var array
     */
    protected $aliases;

    /**
     * @param array $aliases
     */
    public function __construct(array $aliases = [])
    {
        $this->aliases = $aliases;
        $this->sort();
    }

    /**
     * @param string $alias
     * @param string $path
     * @throws \Exception
     */
    public function set($alias, $path)
    {
        if (array_key_exists($alias, $this->aliases)) {
            throw new \Exception("Alias {$alias} already set, use update instead.");
        }
        $this->aliases[$alias] = rtrim($path, '/');
        $this->sort();
    }

    /**
     * @param string $alias
     * @return string
     */
    public function get($alias)
    {
        if (strncmp($alias, '@', 1)) {
            return $alias;
        }
        $keys = array_keys($this->aliases);
        $values = array_values($this->aliases);
        return str_replace($keys, $values, $alias);
    }

    /**
     * @param string $alias
     * @param string $path
     * @throws \Exception
     */
    public function update($alias, $path)
    {
        if (array_key_exists($alias, $this->aliases)) {
            $this->aliases[$alias] = rtrim($path, '/');
        } else {
            throw new \Exception("Alias {$alias} not exists, use set instead.");
        }
    }

    /**
     * @return bool
     */
    public function sort()
    {
        return uksort($this->aliases, function ($a, $b) {
            return strlen($a) < strlen($b);
        });
    }
}
