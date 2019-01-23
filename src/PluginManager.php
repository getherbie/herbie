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
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionClass;

class PluginManager
{
    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $pluginsPath;

    /**
     * @var array
     */
    private $loadedPlugins;

    /**
     * @var array
     */
    private $pluginPaths;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * PluginManager constructor.
     * @param EventManager $eventManager
     * @param Config $config
     * @param ContainerInterface $container
     * @throws SystemException
     */
    public function __construct(
        Config $config,
        EventManager $eventManager,
        ContainerInterface $container
    ) {
        $this->config = $config;
        $this->container = $container;
        $this->eventManager = $eventManager;
        $this->loadedPlugins = [];
        $this->pluginPaths = [];
        $this->pluginsPath = normalize_path($config['paths']['plugins']);
    }

    /**
     * @throws \ReflectionException
     */
    public function init(): void
    {
        // add sys plugins first
        $priority = 900;
        foreach ($this->config['enabledSysPlugins'] as $key) {
            $this->loadPlugin($this->pluginsPath, $key, $priority);
        }

        // add third-party plugins
        $priority = 700;
        foreach ($this->config['enabledPlugins'] as $key) {
            $this->loadPlugin($this->pluginsPath, $key, $priority);
        }

        $this->eventManager->trigger('onPluginsInitialized', $this);
    }

    /**
     * @param string $path
     * @param string $key
     * @param int $priority
     * @throws \ReflectionException
     */
    private function loadPlugin(string $path, string $key, int $priority): void
    {
        $pluginPath = sprintf('%s/%s/%s.php', $path, $key, $key);
        if (is_readable($pluginPath)) {
            require($pluginPath);

            $className = 'herbie\\plugin\\' . $key . '\\' . ucfirst($key) . 'Plugin';

            $class = new ReflectionClass($className);

            $constructor = $class->getConstructor();
            $constructorParams = [];
            if ($constructor) {
                foreach ($constructor->getParameters() as $param) {
                    $classNameToInject = $param->getClass()->getName();
                    $constructorParams[] = $this->container->get($classNameToInject);
                };
            }

            /** @var PluginInterface $plugin */
            $plugin = new $className(...$constructorParams);
            $plugin->attach($this->eventManager, $priority);

            $this->loadedPlugins[$key] = $plugin;
            $this->pluginPaths[$key] = dirname($pluginPath);
        }
    }

    /**
     * @return array
     */
    public function getLoadedPlugins(): array
    {
        return $this->loadedPlugins;
    }

    /**
     * @return array
     */
    public function getMiddlewares(): array
    {
        $middlewares = [];
        foreach ($this->loadedPlugins as $plugin) {
            if ($plugin instanceof MiddlewareInterface) {
                $middlewares[] = $plugin;
            }
        }
        return $middlewares;
    }

    /**
     * @return array
     */
    public function getPluginPaths(): array
    {
        return $this->pluginPaths;
    }
}
