<?php

declare(strict_types=1);

namespace herbie;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

final class Config
{
    private array $data;

    private const DELIM = '.';

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get value by using dot notation for nested arrays.
     *
     * @example $value = $config->get('example.node.value');
     *
     * @param callable|mixed $default
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        $name = trim($name);

        if ($name === '') {
            return $this->data;
        }

        $path = explode(self::DELIM, $name);

        $current = $this->data;
        foreach ($path as $field) {
            if (isset($current[$field])) {
                $current = $current[$field];
            } else {
                return is_callable($default) ? $default() : $default;
            }
        }

        return $current;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getAsArray(string $path, array $default = []): array
    {
        $arrayValue = $this->get($path, $default);
        if (!is_array($arrayValue)) {
            throw new \UnexpectedValueException("Value for \"$path\" is not an array");
        }
        return $arrayValue;
    }

    public function getAsBool(string $path, bool $default = false): bool
    {
        $boolValue = $this->get($path, $default);
        if (!is_bool($boolValue)) {
            throw new \UnexpectedValueException("Value for \"$path\" is not a bool");
        }
        return $boolValue;
    }

    public function getAsFloat(string $path, float $default = 0.0): float
    {
        $floatValue = $this->get($path, $default);
        if (!is_float($floatValue)) {
            throw new \UnexpectedValueException("Value for \"$path\" is not a float");
        }
        return $floatValue;
    }

    public function getAsInt(string $path, int $default = 0): int
    {
        $intValue = $this->get($path, $default);
        if (!is_int($intValue)) {
            throw new \UnexpectedValueException("Value for \"$path\" is not an int");
        }
        return $intValue;
    }

    public function getAsString(string $path, string $default = ''): string
    {
        $strValue = $this->get($path, $default);
        if (!is_string($strValue)) {
            throw new \UnexpectedValueException("Value for \"$path\" is not a string");
        }
        return $strValue;
    }

    public function getAsConfig(string $path): Config
    {
        $data = $this->get($path);
        if ($data === null) {
            throw new \UnexpectedValueException("Config for \"$path\" not found");
        }
        if (!is_array($data)) {
            throw new \UnexpectedValueException("Config for \"$path\" is not an array");
        }
        return new self($data);
    }

    public function check(string $name): bool
    {
        $value = $this->get($name);
        return $value !== null;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function flatten(): array
    {
        $recItIt = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($this->data)
        );
        $flatten = [];
        foreach ($recItIt as $leafValue) {
            $keys = [];
            foreach (range(0, $recItIt->getDepth()) as $depth) {
                $keys[] = $recItIt->getSubIterator($depth)->key();
            }
            $flatten[join(self::DELIM, $keys)] = $leafValue;
        }
        ksort($flatten);
        return $flatten;
    }
}
