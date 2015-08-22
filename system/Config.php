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
     * @param string $sitePath
     * @param string $webPath
     * @param string $webUrl
     */
    public function __construct($sitePath, $webPath, $webUrl)
    {
        $this->appPath  = realpath(__DIR__);
        $this->sitePath = $sitePath;
        $this->webPath  = $webPath;
        $this->webUrl   = preg_replace('#\/?index.php#', '', $webUrl);
        $this->items = [];
        $this->cache = [];
        $this->loadConfig(false);
    }

    private function loadConfig($useCache = true)
    {
        if ($useCache) {
            #$cacheFile = $this->sitePath . '/cache/config.json';
            $cacheFile = $this->sitePath . '/cache/config.php';
            if (is_file($cacheFile)) {
                #$this->items = json_decode(file_get_contents($cacheFile), true);
                $this->items = require($cacheFile);
            } else {
                $this->loadMainFile();
                $this->loadPluginFiles();
                #file_put_contents($cacheFile, json_encode($this->items));
                file_put_contents($cacheFile, '<?php return '.var_export($this->items, true).';');
            }
        } else {
            $this->loadMainFile();
            $this->loadPluginFiles();
        }
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
     * Push an element onto the end of array by using dot notation for nested arrays. Creates a new array if it
     * does not exist.
     *
     * @example $config->push('pages.extra_paths', '@plugin/test/pages');
     *
     * @param string $name
     * @param mixed $value
     * @return int
     */
    public function push($name, $value)
    {
        $path = explode('.', $name);
        $current = &$this->items;
        foreach ($path as $field) {
            if (is_array($current)) {
                if (!isset($current[$field])) {
                    $current[$field] = [];
                }
                $current = &$current[$field];
            }
        }
        $current[] = $value;
        return count($current);
    }

    /**
     * Set value by using dot notation for nested arrays.
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
    private function loadMainFile()
    {
        // vars used in config files
        $APP_PATH = $this->appPath;
        $SITE_PATH = $this->sitePath;
        $WEB_PATH = $this->webPath;
        $WEB_URL = $this->webUrl;

        $defaults = require(__DIR__ . '/../config/defaults.php');
        if (is_file($this->sitePath . '/config/main.php')) {
            $userConfig = require($this->sitePath . '/config.php');
            $defaults = $this->merge($defaults, $userConfig);
        } elseif (is_file($this->sitePath . '/config/main.yml')) {
            $content = file_get_contents($this->sitePath . '/config/main.yml');
            $content = $this->replaceConstants($content);
            $userConfig = Yaml::parse($content);
            $defaults = $this->merge($defaults, $userConfig);
        }
        $this->items = $defaults;
    }

    /**
     */
    private function loadPluginFiles()
    {
        $dir = $this->sitePath . '/config/plugins';
        if (is_readable($dir)) {
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
    
	public function __debugInfo()
	{
        return call_user_func('get_object_vars', $this);
	}
	    
}
