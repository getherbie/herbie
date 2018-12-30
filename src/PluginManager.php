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

use Zend\EventManager\EventManager;

class PluginManager extends EventManager
{

    /** @var array  */
    protected $enabled;

    /** @var string  */
    protected $path;

    /** @var array  */
    protected $loaded;

    /** @var bool  */
    protected $initialized;

    /** @var array */
    protected $enabledSysPlugins;

    protected $application;

    /**
     * PluginManager constructor.
     * @param array $enabled
     * @param string $path
     * @param array $enabledSysPlugins
     * @throws \Exception
     */
    public function __construct(array $enabled, string $path, array $enabledSysPlugins, Application $application)
    {
        $this->enabled = $enabled;
        $this->path = realpath($path);
        $this->loaded = [];
        $this->initialized = false;
        $this->enabledSysPlugins = $enabledSysPlugins;
        $this->application = $application;
        if (false === $this->path) {
            throw new \Exception("Plugins path '{$path}' doesn't exist or isn't readable (see config plugins.path).");
        }
        parent::__construct();
    }

    /**
     * @return bool
     * @throws \RuntimeException
     */
    public function init(): bool
    {
        // add sys plugins first
        foreach ($this->enabledSysPlugins as $key) {
            $this->loadPlugin($this->path, $key);
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
    protected function loadPlugin(string $path, string $key)
    {
        $pluginPath = sprintf('%s/%s/%s.php', $path, $key, $key);
        if (is_readable($pluginPath)) {
            require($pluginPath);

            $className = 'herbie\\plugin\\' . $key . '\\' . ucfirst($key) . 'Plugin';

            $plugin = new $className($this->application);
            $plugin->attach($this);

            $this->loaded[$key] = dirname($pluginPath);
        }
    }

    /**
     * @return bool
     */
    public function isInitialized(): bool
    {
        return true === $this->initialized;
    }

    /**
     * @return array
     */
    public function getLoadedPlugins(): array
    {
        return $this->loaded;
    }
}
