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
    private $events;

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
        $this->events = [];
        $this->dirs = [];
        $this->initialized = false;
    }

    /**
     * @param Config $config
     * @throws \RuntimeException
     */
    public function init(Config $config)
    {
        $this->installSysPlugin('twig', $config);
        $this->installSysPlugin('shortcode', $config);
        $this->installSysPlugin('markdown', $config);
        $this->installSysPlugin('textile', $config);

        $pluginPath = rtrim($config->get('plugins.path'), '/');
        $pluginList = $config->get('plugins.enable', []);
        foreach ($pluginList as $pluginKey) {
            $pluginObj = $this->createPlugin($pluginPath, $pluginKey, $config);
            $this->addPlugin($pluginObj, $pluginKey);
        }

        $this->initialized = true;
    }

    /**
     * @param string $key
     * @param Config $config
     */
    protected function installSysPlugin($key, $config)
    {
        $pluginObj = $this->createPlugin(__DIR__ . '/../plugins/', $key, $config, true);
        $this->addPlugin($pluginObj, $key);
    }

    /**
     * @param $pluginPath
     * @param $pluginKey
     * @param $config
     * @return Plugin
     */
    protected function createPlugin($pluginPath, $pluginKey, $config, $isSystemPlugin = false)
    {
        $filePath = sprintf('%s/%s/%sPlugin.php', $pluginPath, $pluginKey, ucfirst($pluginKey));

        if (!is_file($filePath)) {
            $message = sprintf('Plugin "%s" enabled but not found!', $pluginKey);
            throw new \RuntimeException($message);
        }

        $this->dirs[$pluginKey] = dirname($filePath);

        if ($isSystemPlugin) {
            $pluginClass = '\\herbie\\sysplugin\\' . $pluginKey . '\\' . ucfirst($pluginKey) . 'Plugin';
        } else {
            $pluginClass = '\\herbie\\plugin\\' . $pluginKey . '\\' . ucfirst($pluginKey) . 'Plugin';
        }
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
     * @param mixed $subject
     * @param array $attributes
     * @return mixed
     */
    public function dispatch($eventName, $subject, array $attributes)
    {
        if (!isset($this->events[$eventName])) {
            return $subject;
        }

        foreach ($this->events[$eventName] as $pluginKey) {
            $plugin = $this->plugins[$pluginKey];
            call_user_func([$plugin, $eventName], $subject, $attributes);
        }

        return $subject;
    }

    /**
     * @param Plugin $plugin
     * @param string $pluginKey
     */
    public function addPlugin(Plugin $plugin, $pluginKey)
    {
        if (isset($this->plugins[$pluginKey])) {
            $message = sprintf('Plugin "%s" is already installed!', $pluginKey);
            throw new \Exception($message, 500);
        }
        $this->plugins[$pluginKey] = $plugin;
        foreach ($plugin->getSubscribedEvents() as $eventName) {
            $this->events[$eventName][] = $pluginKey;
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
            $message = sprintf('Plugin "%s" not found!', $pluginKey);
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
    public function getEvents()
    {
        return $this->events;
    }

    public function getPlugins()
    {
        return $this->plugins;
    }

}
