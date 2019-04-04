<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

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
     * @var string
     */
    private $sysPluginsPath;

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
     * @var array
     */
    private $middlewares;

    /**
     * PluginManager constructor.
     * @param Config $config
     * @param EventManager $eventManager
     * @param ContainerInterface $container
     * @param FilterChainManager $filterChainManager
     * @param Translator $translator
     * @param TwigRenderer $twigRenderer
     * @throws SystemException
     */
    public function __construct(
        Config $config,
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
        $this->pluginsPath = normalize_path($config->get('paths.plugins'));
        $this->sysPluginsPath = normalize_path($config->get('paths.sysPlugins'));
        $this->filterChainManager = $filterChainManager;
        $this->twigRenderer = $twigRenderer;
        $this->middlewares = [];
        $this->translator = $translator;
    }

    /**
     * @throws SystemException
     * @throws \ReflectionException
     */
    public function init(): void
    {
        $plugins = array_unique(array_merge(
            explode_list($this->config->get('enabledSysPlugins')),
            explode_list($this->config->get('enabledPlugins'))
        ));

        foreach ($plugins as $key) {
            $this->loadPlugin($key);
        }

        $this->eventManager->trigger('onPluginsAttached', $this);
    }

    /**
     * @param string $key
     * @throws SystemException
     * @throws \ReflectionException
     * @throws \InvalidArgumentException
     */
    private function loadPlugin(string $key): void
    {
        $configKey = sprintf('plugins.%s.pluginPath', $key);
        $pluginPath = $this->config->getAsString($configKey);
        $pluginClassPath = sprintf('%s/plugin.php', $pluginPath);

        if (!is_file($pluginClassPath) || !is_readable($pluginClassPath)) {
            throw SystemException::pluginNotExist($key);
        }

        require($pluginClassPath);

        $declaredClasses = get_declared_classes();
        $pluginClassName = end($declaredClasses);

        $reflectedClass = new \ReflectionClass($pluginClassName);

        $constructor = $reflectedClass->getConstructor();
        $constructorParams = [];
        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                if ($param->getType() === null) {
                    throw SystemException::serverError('Only objects can be injected in ' . $pluginClassName);
                }
                $classNameToInject = $param->getClass()->getName();
                $constructorParams[] = $this->container->get($classNameToInject);
            };
        }

        /** @var PluginInterface $plugin */
        $plugin = new $pluginClassName(...$constructorParams);

        if (!$plugin instanceof PluginInterface) {
            // TODO throw error?
            return;
        }

        if ($plugin->apiVersion() !== HERBIE_API_VERSION) {
            // TODO throw error?
            return;
        }

        foreach ($plugin->events() as $event) {
            $this->attachListener(...$event);
        }
        foreach ($plugin->filters() as $filter) {
            $this->attachFilter(...$filter);
        }
        foreach ($plugin->middlewares() as $middleware) {
            $this->middlewares[] = $middleware;
        }
        foreach ($plugin->twigFilters() as $twigFilter) {
            $this->addTwigFilter(...$twigFilter);
        }
        foreach ($plugin->twigFunctions() as $twigFunction) {
            $this->addTwigFunction(...$twigFunction);
        }
        foreach ($plugin->twigTests() as $twigTest) {
            $this->addTwigTest(...$twigTest);
        }

        $eventName = sprintf('onPlugin%sAttached', ucfirst($key));
        $this->eventManager->trigger($eventName, $plugin);

        $this->translator->addPath($key, $pluginPath . '/messages');

        $this->loadedPlugins[$key] = $plugin;
        $this->pluginPaths[$key] = $pluginPath;
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
     */
    private function attachFilter(string $name, callable $callable): void
    {
        $this->filterChainManager->attach($name, $callable);
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
        $closure = function (Event $event) use ($name, $callable, $options) {
            /** @var TwigRenderer $twig */
            $twig = $event->getTarget();
            $twig->addFilter(
                new TwigFilter($name, $callable, $options)
            );
        };
        return $this->eventManager->attach('onTwigInitialized', $closure);
    }

    /**
     * @param string $name
     * @param callable $callable
     * @param array $options
     * @return callable
     */
    private function addTwigFunction(string $name, callable $callable, array $options = []): callable
    {
        $closure = function (Event $event) use ($name, $callable, $options) {
            /** @var TwigRenderer $twig */
            $twig = $event->getTarget();
            $twig->addFunction(
                new TwigFunction($name, $callable, $options)
            );
        };
        return $this->eventManager->attach('onTwigInitialized', $closure);
    }

    /**
     * @param string $name
     * @param callable $callable
     * @param array $options
     * @return callable
     */
    private function addTwigTest(string $name, callable $callable, array $options = []): callable
    {
        $closure = function (Event $event) use ($name, $callable, $options) {
            /** @var TwigRenderer $twig */
            $twig = $event->getTarget();
            $twig->addTest(
                new TwigTest($name, $callable, $options)
            );
        };
        return $this->eventManager->attach('onTwigInitialized', $closure);
    }
}
