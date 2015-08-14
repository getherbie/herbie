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

class PluginManager
{
    /**
     * @var array
     */
    private $plugins;

    /**
     * @var array
     */
    private $listeners;

    /**
     * @var array
     */
    private $dirs;

    /**
     * @var boolean
     */
    private $initialized;

    /**
     *
     */
    public function __construct()
    {
        $this->plugins =  [];
        $this->listeners = [];
        $this->dirs = [];
        $this->initialized = false;
    }

    /**
     * @param Config $config
     * @throws \RuntimeException
     */
    public function init(Config $config)
    {
        $pluginPath = rtrim($config->get('plugins.path'), '/');
        $pluginList = $config->get('plugins.enable', []);
        foreach ($pluginList as $pluginKey) {
            $pluginObj = $this->createPlugin($pluginPath, $pluginKey, $config);
            $this->addPlugin($pluginObj, $pluginKey);
        }
        $this->initialized = true;
    }

    /**
     * @param $pluginPath
     * @param $pluginKey
     * @param $config
     * @return Plugin
     */
    protected function createPlugin($pluginPath, $pluginKey, $config)
    {
        $filePath = sprintf('%s/%s/%sPlugin.php', $pluginPath, $pluginKey, ucfirst($pluginKey));

        if (!is_file($filePath)) {
            $message = sprintf('Plugin "{%s}" enabled but not found!', $pluginKey);
            throw new \RuntimeException($message);
        }

        $this->dirs[$pluginKey] = dirname($filePath);

        $pluginClass = '\\herbie\\plugin\\' . $pluginKey . '\\' . ucfirst($pluginKey) . 'Plugin';
        return new $pluginClass($config);
    }

    /**
     * @return bool
     */
    public function isInitialized()
    {
        return true === $this->initialized;
    }

    /**
     * @param string $eventName
     * @param Event $event
     * @return Event
     */
    public function dispatch($eventName, Event $event)
    {
        if (!isset($this->listeners[$eventName])) {
            return $event;
        }

        foreach ($this->listeners[$eventName] as $pluginKey) {
            $plugin = $this->plugins[$pluginKey];
            call_user_func([$plugin, $eventName], $event);
        }

        return $event;
    }

    /**
     * @param Plugin $plugin
     * @param string $pluginKey
     */
    public function addPlugin(Plugin $plugin, $pluginKey)
    {
        $this->plugins[$pluginKey] = $plugin;
        foreach ($plugin->getSubscribedEvents() as $eventName) {
            $this->listeners[$eventName][] = $pluginKey;
        }
    }

    /**
     * @param string $pluginKey
     * @return Plugin
     * @throws \Exception
     */
    public function getPlugin($pluginKey)
    {
        if (!isset($this->plugins[$pluginKey])) {
            $message = sprintf('Plugin "{%s}" not found!', $pluginKey);
            throw new \Exception($message, 500);
        }
        return $this->plugins[$pluginKey];
    }

    /**
     * @param string $pluginKey
     * @return bool
     */
    public function hasPlugin($pluginKey)
    {
        return isset($this->plugins[$pluginKey]);
    }

    /**
     * @return array
     */
    public function getDirectories()
    {
        return $this->dirs;
    }

    /**
     * @return array
     */
    public function getListeners()
    {
        return $this->listeners;
    }

}
