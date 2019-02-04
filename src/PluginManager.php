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

use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Twig_Filter;
use Twig_Function;
use Twig_Test;

class PluginManager
{
    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var Configuration
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
     * @var FilterChainManager
     */
    private $filterChainManager;
    /**
     * @var TwigRenderer
     */
    private $twigRenderer;
    /**
     * @var Translator
     */
    private $translator;

    /**
     * PluginManager constructor.
     * @param Configuration $config
     * @param EventManager $eventManager
     * @param ContainerInterface $container
     * @param FilterChainManager $filterChainManager
     * @param Translator $translator
     * @param TwigRenderer $twigRenderer
     * @throws SystemException
     */
    public function __construct(
        Configuration $config,
        EventManager $eventManager,
        FilterChainManager $filterChainManager,
        Translator $translator,
        TwigRenderer $twigRenderer,
        ContainerInterface $container
    ) {
        $this->config = $config;
        $this->container = $container;
        $this->eventManager = $eventManager;
        $this->loadedPlugins = [];
        $this->pluginPaths = [];
        $this->pluginsPath = normalize_path($config['paths']['plugins']);
        $this->filterChainManager = $filterChainManager;
        $this->twigRenderer = $twigRenderer;
        $this->middlewares = [];
        $this->translator = $translator;
    }

    /**
     * @throws \ReflectionException
     */
    public function init(): void
    {
        // add sys plugins first
        foreach ($this->config['enabledSysPlugins'] as $key) {
            $this->loadPlugin($this->pluginsPath, $key);
        }

        // add third-party plugins
        foreach ($this->config['enabledPlugins'] as $key) {
            $this->loadPlugin($this->pluginsPath, $key);
        }

        $this->eventManager->trigger('onPluginsInitialized', $this);
    }

    /**
     * @param string $path
     * @param string $key
     * @throws \ReflectionException
     */
    private function loadPlugin(string $path, string $key): void
    {
        $pluginPath = sprintf('%s/%s/%s.php', $path, $key, $key);
        if (is_readable($pluginPath)) {
            require($pluginPath);

            $className = 'Herbie\\' . ucfirst($key) . 'Plugin';

            $class = new \ReflectionClass($className);

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
            if (method_exists($plugin, 'attach')) {
                $plugin->attach($this->eventManager);
            }

            foreach ($plugin->getEvents() as $event) {
                $this->attachListener(...$event);
            }
            foreach ($plugin->getFilters() as $filter) {
                $this->attachFilter(...$filter);
            }
            foreach ($plugin->getMiddlewares() as $middleware) {
                $this->middlewares[] = $middleware;
            }
            foreach ($plugin->getTwigFilters() as $twigFilter) {
                $this->addTwigFilter(...$twigFilter);
            }
            foreach ($plugin->getTwigFunctions() as $twigFunction) {
                $this->addTwigFunction(...$twigFunction);
            }
            foreach ($plugin->getTwigTests() as $twigTest) {
                $this->addTwigTest(...$twigTest);
            }

            $this->translator->addPath($key, $path . '/messages');

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
        foreach ($this->loadedPlugins as $plugin) {
            if ($plugin instanceof MiddlewareInterface) {
                $this->middlewares[] = $plugin;
            }
        }
        return $this->middlewares;
    }

    /**
     * @return array
     */
    public function getPluginPaths(): array
    {
        return $this->pluginPaths;
    }

    /**
     * @param string $name
     * @param callable $callable
     * @param int $priority
     */
    private function attachFilter(string $name, callable $callable, int $priority = 1): void
    {
        $this->filterChainManager->attach($name, $callable, $priority);
    }

    /**
     * @param string $name
     * @param callable $callable
     * @param int $priority
     */
    private function attachListener(string $name, callable $callable, int $priority = 1): void
    {
        $this->eventManager->attach($name, $callable, $priority);
    }

    /**
     * @param string $name
     * @param callable $callable
     * @param array $options
     * @return callable
     */
    private function addTwigFilter(string $name, callable $callable, array $options = []): callable
    {
        return $this->eventManager->attach('onTwigInitialized', function ($event) use ($name, $callable, $options) {
            /** @var TwigRenderer $twig */
            $twig = $event->getTarget();
            $twig->addFilter(
                new Twig_Filter($name, $callable, $options)
            );
        });
    }

    /**
     * @param string $name
     * @param callable $callable
     * @param array $options
     * @return callable
     */
    private function addTwigFunction(string $name, callable $callable, array $options = []): callable
    {
        return $this->eventManager->attach('onTwigInitialized', function ($event) use ($name, $callable, $options) {
            /** @var TwigRenderer $twig */
            $twig = $event->getTarget();
            $twig->addFunction(
                new Twig_Function($name, $callable, $options)
            );
        });
    }

    /**
     * @param string $name
     * @param callable $callable
     * @param array $options
     * @return callable
     */
    private function addTwigTest(string $name, callable $callable, array $options = []): callable
    {
        return $this->eventManager->attach('onTwigInitialized', function ($event) use ($name, $callable, $options) {
            /** @var TwigRenderer $twig */
            $twig = $event->getTarget();
            $twig->addTest(
                new Twig_Test($name, $callable, $options)
            );
        });
    }
}
