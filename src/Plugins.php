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

class Plugins
{

    /**
     * @var array
     */
    private $items;

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
        $this->items = [];
        $this->dirs = null;
        $this->initialized = false;
    }

    /**
     * @param Container $container
     * @throws \RuntimeException
     */
    public function init(Container $container)
    {
        // Retrieve services from container
        $events = $container['EventDispatcher'];
        $config = $container['Config'];

        $pluginPath = rtrim($config->get('plugins.path'), '/');
        $pluginList = $config->get('plugins.enable', []);
        foreach ($pluginList as $pluginKey) {
            $filePath = sprintf(
                '%s/%s/%sPlugin.php', $pluginPath, $pluginKey, ucfirst($pluginKey)
            );
            
            if (!is_file($filePath)) {
                $message = sprintf('Plugin "{%s}" enabled but not found!', $pluginKey);
                throw new \RuntimeException($message);
            }

            $pluginClass = '\\herbie\\plugin\\' . $pluginKey . '\\' . ucfirst($pluginKey) . 'Plugin';
            $instance = new $pluginClass($config);
            $events->addSubscriber($instance);
            $this->addItem($pluginKey, $filePath, $pluginClass);
        }
        $this->initialized = true;
    }

    /**
     * @param string $key
     * @param string $path
     * @param string $class
     */
    private function addItem($key, $path, $class)
    {
        $dir = dirname($path);
        $this->items[] = [
            'key' => $key,
            'dir' => $dir,
            'path' => $path,
            'class' => $class
        ];
        $this->dirs[$key] = $dir;
    }

    /**
     * @return array
     */
    public function getDirectories()
    {
        return $this->dirs;
    }

    /**
     * @return bool
     */
    public function isInitialized()
    {
        return true === $this->initialized;
    }

}
