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

class Alias
{

    /**
     * @var array
     */
    private $aliases;

    /**
     * @param array $aliases
     * @throws \Exception
     */
    public function __construct(array $aliases = [])
    {
        $this->aliases = [];
        foreach ($aliases as $alias => $path) {
            $this->set($alias, $path);
        }
    }

    /**
     * @param string $alias
     * @param string $path
     * @throws \Exception
     */
    public function set(string $alias, string $path): void
    {
        if (array_key_exists($alias, $this->aliases)) {
            throw new \Exception("Alias {$alias} already set, use update instead.");
        }
        $this->aliases[$alias] = $this->rtrim($path);
    }

    /**
     * @param string $alias
     * @return string
     */
    public function get(string $alias): string
    {
        if (strncmp($alias, '@', 1)) {
            return $alias;
        }
        return strtr($alias, $this->aliases);
    }

    /**
     * @param string $alias
     * @param string $path
     * @throws \Exception
     */
    public function update(string $alias, string $path): void
    {
        if (array_key_exists($alias, $this->aliases)) {
            $this->aliases[$alias] = $this->rtrim($path);
        } else {
            throw new \Exception("Alias {$alias} not exists, use set instead.");
        }
    }

    /**
     * @param string $path
     * @return string
     */
    private function rtrim(string $path): string
    {
        return rtrim($path, '/');
    }
}
