<?php

/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

final class Config
{
    private array $data;

    private string $delim;

    public function __construct(array $data, string $delim = '.')
    {
        $this->data = $data;
        $this->delim = $delim;
    }

    /**
     * Get value by using dot notation for nested arrays.
     *
     * @example $value = $config->get('twig.extend.functions');
     *
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        $path = explode($this->delim, $name);

        if ($path === false) {
            return null;
        }

        $current = $this->data;
        foreach ($path as $field) {
            if (isset($current[$field])) {
                $current = $current[$field];
            } else {
                return $default;
            }
        }

        return $current;
    }

    public function getAsArray(string $path, array $default = []): array
    {
        $arrayValue = $this->get($path, $default);
        if (!is_array($arrayValue)) {
            throw new \UnexpectedValueException("Value for \"$path\" is not an array");
        }
        return (array)($arrayValue);
    }

    public function getAsBool(string $path, bool $default = false): bool
    {
        $boolValue = $this->get($path, $default);
        if (!is_bool($boolValue)) {
            throw new \UnexpectedValueException("Value for \"$path\" is not a bool");
        }
        return boolval($boolValue);
    }

    public function getAsFloat(string $path, float $default = 0.0): float
    {
        $floatValue = $this->get($path, $default);
        if (!is_float($floatValue)) {
            throw new \UnexpectedValueException("Value for \"$path\" is not a float");
        }
        return floatval($floatValue);
    }

    public function getAsInt(string $path, int $default = 0): int
    {
        $intValue = $this->get($path, $default);
        if (!is_int($intValue)) {
            throw new \UnexpectedValueException("Value for \"$path\" is not an int");
        }
        return intval($intValue);
    }

    public function getAsString(string $path, string $default = ''): string
    {
        $strValue = $this->get($path, $default);
        if (!is_string($strValue)) {
            throw new \UnexpectedValueException("Value for \"$path\" is not a string");
        }
        return strval($strValue);
    }

    public function getAsConfig(string $path): Config
    {
        $data = $this->get($path, null);
        if (is_null($data)) {
            throw new \UnexpectedValueException("Config for \"$path\" not found");
        }
        if (!is_array($data)) {
            throw new \UnexpectedValueException("Config for \"$path\" is not an array");
        }
        return new self($data, $this->delim);
    }

    public function check(string $name): bool
    {
        $value = $this->get($name);
        return $value !== null;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
