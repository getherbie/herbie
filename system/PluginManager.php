<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

class PluginManager
{

    /** @var array  */
    private $enabled;

    /** @var string  */
    private $path;

    /** @var array  */
    private $loaded;

    /** @var bool  */
    private $initialized;

    /** @var array */
    private $enabledSysPlugins;

    /**
     * @param array $enabled
     * @param $path
     */
    public function __construct(array $enabled, $path, array $enabledSysPlugins)
    {
        $this->enabled = $enabled;
        $this->path = realpath($path);
        $this->loaded = [];
        $this->initialized = false;
        $this->enabledSysPlugins = $enabledSysPlugins;
    }

    /**
     * @return bool
     * @throws \RuntimeException
     */
    public function init()
    {
        // add system plugins
        $path = realpath(__DIR__ . '/../plugins');
        foreach ($this->enabledSysPlugins as $key) {
            $this->loadPlugin($path, $key);
        }

        // add third-party plugins
        foreach ($this->enabled as $key) {
            $this->loadPlugin($this->path, $key);
        }

        $this->initialized = true;
        return $this->initialized;
    }

    /**
     * @param string $path
     * @param string $key
     */
    protected function loadPlugin($path, $key)
    {
        $pluginPath = sprintf('%s/%s/%s.php', $path, $key, $key);
        if (is_readable($pluginPath)) {
            require($pluginPath);
            $this->loaded[$key] = dirname($pluginPath);
        }
    }

    /**
     * @return bool
     */
    public function isInitialized()
    {
        return true === $this->initialized;
    }

    /**
     * @return array
     */
    public function getLoadedPlugins()
    {
        return $this->loaded;
    }

}
