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
     * @var string
     */
    private $appPath;

    /**
     * @var string
     */
    private $webPath;

    /**
     * @var string
     */
    private $webUrl;

    /**
     * @var string
     */
    private $sitePath;

    /**
     * @param $appPath string
     * @param $sitePath string
     * @param $webPath string
     * @param $webUrl string
     */
    public function __construct($appPath, $sitePath, $webPath, $webUrl)
    {
        $this->appPath  = $appPath;
        $this->sitePath = $sitePath;
        $this->webPath  = $webPath;
        $this->webUrl   = $webUrl;
        $this->items = $this->loadFiles();
        $this->loadPluginFiles();
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
     * @return array
     */
    private function loadFiles()
    {
        // vars used in config files
        $APP_PATH = $this->appPath;
        $SITE_PATH = $this->sitePath;
        $WEB_PATH = $this->webPath;
        $WEB_URL = $this->webUrl;

        $defaults = require(__DIR__ . '/defaults.php');
        if (is_file($this->sitePath . '/config.php')) {
            $userConfig = require($this->sitePath . '/config.php');
            $defaults = $this->merge($defaults, $userConfig);
        } elseif (is_file($this->sitePath . '/config.yml')) {
            $content = file_get_contents($this->sitePath . '/config.yml');
            $content = $this->replaceConstants($content);
            $userConfig = Yaml::parse($content);
            $defaults = $this->merge($defaults, $userConfig);
        }
        return $defaults;
    }

    /**
     */
    private function loadPluginFiles()
    {
        $dir = $this->sitePath . '/config/plugins';
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $basename = pathinfo($file, PATHINFO_FILENAME);
                $content = $this->loadFile($dir . '/' . $file);
                $this->set('plugins.config.' . $basename, Yaml::parse($content));
            }
        }
    }

    /**
     * @param string $file
     * @return mixed|string
     */
    private function loadFile($file)
    {
        $content = file_get_contents($file);
        return $this->replaceConstants($content);
    }

    /**
     * @param string $string
     * @return string
     */
    private function replaceConstants($string)
    {
        return str_replace(
            ['APP_PATH', 'WEB_PATH', 'WEB_URL', 'SITE_PATH'],
            [$this->appPath, $this->webPath, $this->webUrl, $this->sitePath],
            $string
        );
    }
}
