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
use Zend\EventManager\EventManagerInterface;

class PluginManager
{
    /**
     * @var
     */
    private $eventManager;

    /** @var array */
    private $enabledPlugins;

    /** @var string */
    private $path;

    /** @var array */
    private $loadedPlugins;

    /** @var array */
    private $pluginPaths;

    /** @var array */
    private $enabledSysPlugins;

    /** @var Application */
    private $application;

    /**
     * PluginManager constructor.
     * @param EventManagerInterface $eventManager
     * @param array $enabledPlugins
     * @param string $path
     * @param array $enabledSysPlugins
     * @param ContainerInterface $container
     * @throws SystemException
     */
    public function __construct(
        EventManagerInterface $eventManager,
        array $enabledPlugins,
        string $path,
        array $enabledSysPlugins,
        ContainerInterface $container
    ) {
        $this->eventManager = $eventManager;
        $this->enabledPlugins = $enabledPlugins;
        $this->path = realpath($path);
        $this->loadedPlugins = [];
        $this->pluginPaths = [];
        $this->enabledSysPlugins = $enabledSysPlugins;
        $this->container = $container;
        if (false === $this->path) {
            throw SystemException::directoryNotExist($path);
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function init(): void
    {
        // add sys plugins first
        $priority = 900;
        foreach ($this->enabledSysPlugins as $key) {
            $this->loadPlugin($this->path, $key, $priority);
        }

        // add third-party plugins
        $priority = 700;
        foreach ($this->enabledPlugins as $key) {
            $this->loadPlugin($this->path, $key, $priority);
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

            /** @var Plugin $plugin */
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
