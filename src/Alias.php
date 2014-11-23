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
     */
    public function set($alias, $path)
    {
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
     * @return bool
     */
    public function sort()
    {
        return uksort($this->aliases, function($a, $b) {
            return strlen($a) < strlen($b);
        });
    }
}
