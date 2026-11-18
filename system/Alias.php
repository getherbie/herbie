<?php

declare(strict_types=1);

namespace herbie;

use InvalidArgumentException;

final class Alias
{
    /** @var array<string, string> */
    private array $aliases = [];

    /**
     * Alias constructor.
     */
    /** @param array<string, string> $aliases */
    public function __construct(array $aliases = [])
    {
        foreach ($aliases as $alias => $path) {
            $this->set($alias, $path);
        }
    }

    public function set(string $alias, string $path): void
    {
        if (array_key_exists($alias, $this->aliases)) {
            throw new InvalidArgumentException("Alias {$alias} already set, use update instead.");
        }
        $this->setInternal($alias, $path);
    }

    private function setInternal(string $alias, string $path): void
    {
        $alias = trim($alias);
        if (!str_starts_with($alias, '@')) {
            throw new InvalidArgumentException("Invalid alias {$alias}, @ char missing.");
        }
        if (substr($alias, 1) === '') {
            throw new InvalidArgumentException("Alias {$alias} is empty.");
        }
        if (strpos($alias, '@', 1) !== false) {
            throw new InvalidArgumentException("Invalid alias {$alias}, @ char only allowed once.");
        }
        $this->aliases[$alias] = str_untrailing_slash($path);
    }

    /** @return array<string, string> */
    public function getAll(): array
    {
        return $this->aliases;
    }

    /**
     * Resolves an alias at the beginning of the given path, e.g. "@page/about.md".
     *
     * Only the leading alias is replaced, and only as a whole segment: "@page"
     * matches "@page/about.md", but not "@pages/about.md". A path without an
     * alias is returned unchanged.
     *
     * @throws InvalidArgumentException if the path starts with an unknown alias
     */
    public function get(string $alias): string
    {
        if (!str_starts_with($alias, '@')) {
            return $alias;
        }

        $name = strtok($alias, '/');
        if ($name === false || !array_key_exists($name, $this->aliases)) {
            throw new InvalidArgumentException(sprintf('Alias %s does not exist.', $name));
        }

        $rest = substr($alias, strlen($name));
        return $this->aliases[$name] . $rest;
    }

    public function update(string $alias, string $path): void
    {
        if (!array_key_exists($alias, $this->aliases)) {
            throw new InvalidArgumentException("Alias {$alias} not exists, use set instead.");
        }
        $this->setInternal($alias, $path);
    }
}
