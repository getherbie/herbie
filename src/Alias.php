<?php

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
        if (array_key_exists($alias, $this->aliases)) {
            throw new \InvalidArgumentException("Alias {$alias} already set, use update instead.");
        }
        $this->setInternal($alias, $path);
    }

    public function get(string $alias): string
    {
        return strtr($alias, $this->aliases);
    }

    public function update(string $alias, string $path): void
    {
        if (!array_key_exists($alias, $this->aliases)) {
            throw new \InvalidArgumentException("Alias {$alias} not exists, use set instead.");
        }
        $this->setInternal($alias, $path);
    }

    private function setInternal(string $alias, string $path): void
    {
        $alias = trim($alias);
        if (strncmp($alias, '@', 1) <> 0) {
            throw new \InvalidArgumentException("Invalid alias {$alias}, @ char missing.");
        }
        if (substr($alias, 1) === '') {
            throw new \InvalidArgumentException("Alias {$alias} is empty.");
        }
        if (strpos($alias, '@', 1) !== false) {
            throw new \InvalidArgumentException("Invalid alias {$alias}, @ char only allowed once.");
        }
        $this->aliases[$alias] = str_untrailing_slash($path);
    }
}
