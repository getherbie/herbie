<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

class Config
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $delim;

    /**
     * @param array $data
     * @param string $delim
     */
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
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        $path = explode($this->delim, $name);
        $current = $this->data;
        foreach ($path as $field) {
            if (isset($current) && isset($current[$field])) {
                $current = $current[$field];
            } elseif (is_array($current) && isset($current[$field])) {
                $current = $current[$field];
            } else {
                return $default;
            }
        }

        return $current;
    }

    /**
     * @param string $path
     * @param array $default
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getAsArray(string $path, array $default = []): array
    {
        $arrayValue = $this->get($path, $default);
        if (!is_array($arrayValue)) {
            throw new \InvalidArgumentException("Value for \"$path\" not an array");
        }
        return (array)($arrayValue);
    }

    /**
     * @param string $path
     * @param bool $default
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function getAsBool(string $path, bool $default = false): bool
    {
        $boolValue = $this->get($path, $default);
        if (!is_bool($boolValue)) {
            throw new \InvalidArgumentException("Value for \"$path\" not a bool");
        }
        return boolval($boolValue);
    }

    /**
     * @param string $path
     * @param float $default
     * @return float
     * @throws \InvalidArgumentException
     */
    public function getAsFloat(string $path, float $default = 0.0): float
    {
        $floatValue = $this->get($path, $default);
        if (!is_float($floatValue)) {
            throw new \InvalidArgumentException("Value for \"$path\" not a float");
        }
        return floatval($floatValue);
    }

    /**
     * @param string $path
     * @param int $default
     * @return int
     * @throws \InvalidArgumentException
     */
    public function getAsInt(string $path, int $default = 0): int
    {
        $intValue = $this->get($path, $default);
        if (!is_int($intValue)) {
            throw new \InvalidArgumentException("Value for \"$path\" not an int");
        }
        return intval($intValue);
    }

    /**
     * @param string $path
     * @param string $default
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getAsString(string $path, string $default = ''): string
    {
        $strValue = $this->get($path, $default);
        if (!is_string($strValue)) {
            throw new \InvalidArgumentException("Value for \"$path\" not a string");
        }
        return strval($strValue);
    }

    /**
     * @param string $path
     * @return Config
     * @throws \InvalidArgumentException
     */
    public function getAsConfig(string $path): Config
    {
        $data = $this->get($path, null);
        if (is_null($data)) {
            throw new \InvalidArgumentException("Config for \"$path\" not found");
        }
        if (!is_array($data)) {
            throw new \InvalidArgumentException("Config for \"$path\" not an array");
        }
        return new static($data, $this->delim);
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function check($name): bool
    {
        $value = $this->get($name);
        return $value !== null;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
