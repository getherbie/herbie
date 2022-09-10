<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

final class Alias
{
    private array $aliases;

    /**
     * Alias constructor.
     */
    public function __construct(array $aliases = [])
    {
        $this->aliases = [];
        foreach ($aliases as $alias => $path) {
            $this->set($alias, $path);
        }
    }

    public function set(string $alias, string $path): void
    {
        if (strncmp($alias, '@', 1) <> 0) {
            throw new \InvalidArgumentException("Invalid alias {$alias}, @ char missing.");
        }
        if (array_key_exists($alias, $this->aliases)) {
            throw new \InvalidArgumentException("Alias {$alias} already set, use update instead.");
        }
        $this->aliases[$alias] = $this->rtrim($path);
    }

    public function get(string $alias): string
    {
        if (strncmp($alias, '@', 1)) {
            return $alias;
        }
        return strtr($alias, $this->aliases);
    }

    public function update(string $alias, string $path): void
    {
        if (array_key_exists($alias, $this->aliases)) {
            $this->aliases[$alias] = $this->rtrim($path);
        } else {
            throw new \InvalidArgumentException("Alias {$alias} not exists, use set instead.");
        }
    }

    private function rtrim(string $path): string
    {
        return rtrim($path, '/');
    }
}
