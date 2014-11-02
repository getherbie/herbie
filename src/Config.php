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

use Symfony\Component\Yaml\Yaml;

class Config
{
    /**
     * @var array
     */
    private $items;

    /**
     * Constructor
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->items = $this->loadFiles($app);
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
        $path = explode('.', $name);
        $current = $this->items;
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
     * Sey value by using dot notation for nested arrays.
     *
     * @example $value = $config->set('twig.cache', false);
     *
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value)
    {
        $path = explode('.', $name);
        $current = &$this->items;
        foreach ($path as $field) {
            if (is_array($current)) {
                // Handle objects.
                if (!isset($current[$field])) {
                    $current[$field] = [];
                }
                $current = &$current[$field];
            } else {
                // Handle arrays and scalars.
                if (!is_array($current)) {
                    $current = [$field => []];
                } elseif (!isset($current[$field])) {
                    $current[$field] = [];
                }
                $current = &$current[$field];
            }
        }

        $current = $value;
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function isEmpty($name)
    {
        $value = $this->get($name);
        return empty($value);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * @param array $default
     * @param array $override
     * @return array
     */
    private function merge($default, $override)
    {
        foreach ($override as $key => $value) {
            if (is_array($value)) {
                $array = isset($default[$key]) ? $default[$key] : [];
                $default[$key] = $this->merge($array, $override[$key]);
            } else {
                $default[$key] = $value;
            }
        }
        return $default;
    }

    /**
     * @param Application $app
     * @return array
     */
    private function loadFiles(Application $app)
    {
        $defaults = require(__DIR__ . '/defaults.php');
        if (is_file($app['sitePath'] . '/config.php')) {
            $userConfig = require($app['sitePath'] . '/config.php');
            return $this->merge($defaults, $userConfig);
        }
        if (is_file($app['sitePath'] . '/config.yml')) {
            $content = file_get_contents($app['sitePath'] . '/config.yml');
            $content = str_replace(
                ['APP_PATH', 'WEB_PATH', 'SITE_PATH'],
                [$app['appPath'], $app['sitePath'], $app['sitePath']],
                $content
            );
            $userConfig = Yaml::parse($content);
            return $this->merge($defaults, $userConfig);
        }
        return $defaults;
    }
}
