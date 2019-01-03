<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie;

use Herbie\Exception\SystemException;
use Zend\EventManager\EventManager;

class PluginManager extends EventManager
{

    /** @var array */
    protected $enabled;

    /** @var string */
    protected $path;

    /** @var array */
    protected $loaded;

    /** @var bool */
    protected $initialized;

    /** @var array */
    protected $enabledSysPlugins;

    /** @var Application */
    protected $application;

    /**
     * PluginManager constructor.
     * @param array $enabled
     * @param string $path
     * @param array $enabledSysPlugins
     * @param Application $application
     * @throws SystemException
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
            throw SystemException::directoryNotExist($path);
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
        $priority = 900;
        foreach ($this->enabledSysPlugins as $key) {
            $this->loadPlugin($this->path, $key, $priority);
        }

        // add third-party plugins
        $priority = 700;
        foreach ($this->enabled as $key) {
            $this->loadPlugin($this->path, $key, $priority);
        }

        $this->initialized = true;
        return $this->initialized;
    }

    /**
     * @param string $path
     * @param string $key
     * @param int $priority
     */
    protected function loadPlugin(string $path, string $key, int $priority): void
    {
        $pluginPath = sprintf('%s/%s/%s.php', $path, $key, $key);
        if (is_readable($pluginPath)) {
            require($pluginPath);

            $className = 'herbie\\plugin\\' . $key . '\\' . ucfirst($key) . 'Plugin';

            /** @var Plugin $plugin */
            $plugin = new $className($this->application);
            $plugin->attach($this, $priority);

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
