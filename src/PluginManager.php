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
        $this->sysPluginsPath = normalize_path($config['paths']['sysPlugins']);
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
        // add sys plugins first
        foreach ($this->config['enabledSysPlugins'] as $key) {
            $this->loadPlugin($this->sysPluginsPath, $key, 'herbie\\sysplugins\\');
        }

        // add third-party plugins
        foreach ($this->config['enabledPlugins'] as $key) {
            $this->loadPlugin($this->pluginsPath, $key, 'herbie\\plugins\\');
        }

        $this->eventManager->trigger('onPluginsAttached', $this);
    }

    /**
     * @param string $path
     * @param string $key
     * @param string $namespace
     * @throws SystemException
     * @throws \ReflectionException
     */
    private function loadPlugin(string $path, string $key, string $namespace): void
    {
        $pluginPath = sprintf('%s/%s/%s.php', $path, $key, $key);
        if (is_readable($pluginPath)) {
            require($pluginPath);

            $className = $namespace . $key . '\\' . ucfirst($key) . 'Plugin';

            $class = new \ReflectionClass($className);

            $constructor = $class->getConstructor();
            $constructorParams = [];
            if ($constructor) {
                foreach ($constructor->getParameters() as $param) {
                    if ($param->getType() === null) {
                        throw SystemException::serverError('Only objects can be injected in ' . $className);
                    }
                    $classNameToInject = $param->getClass()->getName();
                    $constructorParams[] = $this->container->get($classNameToInject);
                };
            }

            /** @var PluginInterface $plugin */
            $plugin = new $className(...$constructorParams);

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
