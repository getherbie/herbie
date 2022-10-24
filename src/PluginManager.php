<?php

declare(strict_types=1);

namespace herbie;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;

final class PluginManager
{
    private EventManager $eventManager;

    private Config $config;

    /** @var array<string, InstallablePlugin> */
    private array $loadedPlugins;

    /** @var array<string, string> */
    private array $pluginPaths;

    private ContainerInterface $container;

    private FilterChainManager $filterChainManager;

    private Translator $translator;

    private LoggerInterface $logger;

    /** @var array<int, MiddlewareInterface|callable|string> */
    private array $appMiddlewares;

    /** @var array<int, array{string, MiddlewareInterface|callable|string}> */
    private array $routeMiddlewares;

    /** @var string[] */
    private array $commands;

    /**
     * PluginManager constructor.
     */
    public function __construct(
        Config $config,
        EventManager $eventManager,
        FilterChainManager $filterChainManager,
        Translator $translator,
        LoggerInterface $logger,
        ContainerInterface $container
    ) {
        $this->config = $config;
        $this->container = $container;
        $this->eventManager = $eventManager;
        $this->filterChainManager = $filterChainManager;
        $this->loadedPlugins = [];
        $this->logger = $logger;
        $this->appMiddlewares = [];
        $this->routeMiddlewares = [];
        $this->pluginPaths = [];
        $this->translator = $translator;
    }

    public function init(): void
    {
        $this->loadPlugin(new InstallablePlugin(
            'virtual_core_plugin',
            __DIR__,
            VirtualCorePlugin::class,
            'virtual',
        ));

        $enabledSystemPlugins = str_explode_filtered($this->config->getAsString('enabledSysPlugins'), ',');
        $enabledComposerOrLocalPlugins = str_explode_filtered($this->config->getAsString('enabledPlugins'), ',');

        // system plugins
        foreach ($this->getInstallablePlugins($enabledSystemPlugins, 'system') as $plugin) {
            $this->loadPlugin($plugin);
        }
        $this->eventManager->trigger('onSystemPluginsAttached', $this);

        // composer plugins
        foreach ($this->getInstallablePlugins($enabledComposerOrLocalPlugins, 'composer') as $plugin) {
            $this->loadPlugin($plugin);
        }
        $this->eventManager->trigger('onComposerPluginsAttached', $this);

        // local plugins
        foreach ($this->getInstallablePlugins($enabledComposerOrLocalPlugins, 'local') as $plugin) {
            $this->loadPlugin($plugin);
        }
        $this->eventManager->trigger('onLocalPluginsAttached', $this);

        $this->loadPlugin(new InstallablePlugin(
            'virtual_local_plugin',
            __DIR__,
            VirtualLocalPlugin::class,
            'virtual',
        ));

        $this->loadPlugin(new InstallablePlugin(
            'virtual_app_plugin',
            __DIR__,
            VirtualAppPlugin::class,
            'virtual',
        ));

        $this->eventManager->trigger('onPluginsAttached', $this);

        $this->loadPlugin(new InstallablePlugin(
            'virtual_last_plugin',
            __DIR__,
            VirtualLastPlugin::class,
            'virtual',
        ));
    }

    /**
     * @param string[] $enabledPlugins
     * @return InstallablePlugin[]
     */
    private function getInstallablePlugins(array $enabledPlugins, string $type): array
    {
        $plugins = [];
        foreach ($enabledPlugins as $pluginKey) {
            $pluginConfigPath = sprintf('plugins.%s', $pluginKey);
            $pluginConfig = $this->config->getAsArray($pluginConfigPath);
            if (
                empty($pluginConfig)
                || ($pluginConfig['location'] !== $type)
                || empty($pluginConfig['pluginName'])
                || empty($pluginConfig['pluginClass'])
                || empty($pluginConfig['pluginPath'])
            ) {
                continue;
            }
            $plugins[] = new InstallablePlugin(
                $pluginConfig['pluginName'],
                $pluginConfig['pluginPath'],
                $pluginConfig['pluginClass'],
                $type
            );
        }

        return $plugins;
    }

    private function loadPlugin(InstallablePlugin $installablePlugin): void
    {
        $plugin = $installablePlugin->createPluginInstance($this->container);

        if ($plugin->apiVersion() < Application::VERSION_API) {
            return; // TODO log info
        }

        foreach ($plugin->commands() as $command) {
            $this->addCommand($command);
        }

        foreach ($plugin->filters() as $filter) {
            $this->addFilter(...$filter);
        }

        foreach ($plugin->appMiddlewares() as $appMiddleware) {
            $this->addAppMiddleware($appMiddleware);
        }

        foreach ($plugin->routeMiddlewares() as $routeMiddleware) {
            $this->addRouteMiddleware($routeMiddleware);
        }

        foreach ($plugin->twigFilters() as $twigFilter) {
            if ($twigFilter instanceof \Twig\TwigFilter) {
                $this->addTwigFilter($twigFilter);
            } elseif ($twigFilter instanceof \herbie\TwigFilter) {
                $this->addTwigFilter($twigFilter->createTwigFilter());
            } else {
                $this->addTwigFilter(new \Twig\TwigFilter(...$twigFilter));
            }
        }

        foreach ($plugin->twigGlobals() as $twigGlobalName => $twigGlobalMixed) {
            $this->addTwigGlobal($twigGlobalName, $twigGlobalMixed);
        }

        foreach ($plugin->twigFunctions() as $twigFunction) {
            if ($twigFunction instanceof \Twig\TwigFunction) {
                $this->addTwigFunction($twigFunction);
            } elseif ($twigFunction instanceof \herbie\TwigFunction) {
                $this->addTwigFunction($twigFunction->createTwigFunction());
            } else {
                $this->addTwigFunction(new \Twig\TwigFunction(...$twigFunction));
            }
        }

        foreach ($plugin->twigTests() as $twigTest) {
            if ($twigTest instanceof \Twig\TwigTest) {
                $this->addTwigTest($twigTest);
            } elseif ($twigTest instanceof \herbie\TwigTest) {
                $this->addTwigTest($twigTest->createTwigTest());
            } else {
                $this->addTwigTest(new \Twig\TwigTest(...$twigTest));
            }
        }

        foreach ($plugin->events() as $event) {
            $this->addListener(...$event);
        }

        $key = $installablePlugin->getKey();

        $eventName = sprintf('onPlugin%sAttached', ucfirst($key));
        $this->eventManager->trigger($eventName, $plugin);

        $this->translator->addPath($key, $installablePlugin->getPath() . '/messages');

        $this->loadedPlugins[$key] = $installablePlugin;
        $this->pluginPaths[$key] = $installablePlugin->getPath();

        $message = sprintf(
            'Plugin %s with type %s installed successfully',
            $installablePlugin->getKey(),
            $installablePlugin->getType(),
        );
        $this->logger->debug($message);
    }

    /**
     * @return array<string, InstallablePlugin>
     */
    public function getLoadedPlugins(): array
    {
        return $this->loadedPlugins;
    }

    /**
     * @return string[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @return array<int, MiddlewareInterface|callable|string>
     */
    public function getAppMiddlewares(): array
    {
        return $this->appMiddlewares;
    }

    /**
     * @return array<int, array{string, MiddlewareInterface|callable|string}>
     */
    public function getRouteMiddlewares(): array
    {
        return $this->routeMiddlewares;
    }

    /**
     * @return array<string, string>
     */
    public function getPluginPaths(): array
    {
        return $this->pluginPaths;
    }

    private function addCommand(string $command): void
    {
        $this->commands[] = $command;
    }

    private function addFilter(string $name, callable $callable): void
    {
        $this->filterChainManager->attach($name, $callable);
    }

    /**
     * @param MiddlewareInterface|callable|string $middleware
     * @return void
     */
    private function addAppMiddleware($middleware): void
    {
        $this->appMiddlewares[] = $middleware;
    }

    /**
     * @param array{string, MiddlewareInterface|callable|string} $routeWithMiddleware
     * @return void
     */
    private function addRouteMiddleware(array $routeWithMiddleware): void
    {
        $this->routeMiddlewares[] = $routeWithMiddleware;
    }

    private function addListener(string $name, callable $callable, int $priority = 1): void
    {
        $this->eventManager->attach($name, $callable, $priority);
    }

    private function addTwigFilter(\Twig\TwigFilter $filter): callable
    {
        $closure = function (Event $event) use ($filter) {
            /** @var TwigRenderer $twig */
            $twig = $event->getTarget();
            $twig->addFilter($filter);
        };
        return $this->eventManager->attach('onTwigInitialized', $closure);
    }

    /**
     * @param mixed $mixed
     */
    private function addTwigGlobal(string $name, $mixed): callable
    {
        $closure = function (Event $event) use ($name, $mixed) {
            /** @var TwigRenderer $twig */
            $twig = $event->getTarget();
            $twig->addGlobal($name, $mixed);
        };
        return $this->eventManager->attach('onTwigInitialized', $closure);
    }

    private function addTwigFunction(\Twig\TwigFunction $function): callable
    {
        $closure = function (Event $event) use ($function) {
            /** @var TwigRenderer $twig */
            $twig = $event->getTarget();
            $twig->addFunction($function);
        };
        return $this->eventManager->attach('onTwigInitialized', $closure);
    }

    private function addTwigTest(\Twig\TwigTest $test): callable
    {
        $closure = function (Event $event) use ($test) {
            /** @var TwigRenderer $twig */
            $twig = $event->getTarget();
            $twig->addTest($test);
        };
        return $this->eventManager->attach('onTwigInitialized', $closure);
    }
}
