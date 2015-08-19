<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Cache;

use Herbie\Config;

class CacheFactory
{

    /**
     * @param string $type
     * @param Config $config
     * @return CacheInterface
     */
    public static function create($type, Config $config)
    {
        $type = strtolower($type);
        $class = 'filesystem';
        if ($config->isEmpty("cache.{$type}.enable") || !in_array($type, ['data', 'page'])) {
            $class = 'dummy';
        }
        $class = 'Herbie\Cache\\' . ucfirst($class) . 'Cache';
        if (!class_exists($class)) {
            throw new \Exception("Missing cache class $class.");
        }
        $options = $config->get("cache.{$type}", []);
        return new $class($options);
    }
}
