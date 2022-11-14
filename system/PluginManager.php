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
    private array $applicationMiddlewares;

    /** @var array<int, array{string, MiddlewareInterface|callable|string}> */
    private array $routeMiddlewares;

    /** @var string[] */
    private array $consoleCommands;

    /**
     * PluginManager constructor.
     */
    public function __construct(
        Config $config,
        EventManager $eventManager,
        FilterChainManager $filterChainManager,
        Translator $translator,
        LoggerInterface $logger,
        ContainerInterface $container // bad but necessary to enable constructor injection of the plugins
    ) {
        $this->config = $config;
        $this->container = $container;
        $this->eventManager = $eventManager;
        $this->filterChainManager = $filterChainManager;
        $this->loadedPlugins = [];
        $this->logger = $logger;
        $this->applicationMiddlewares = [];
        $this->routeMiddlewares = [];
        $this->pluginPaths = [];
        $this->translator = $translator;
    }

    public function init(): void
    {
        $this->loadInstallablePlugin(new InstallablePlugin(
            'CORE',
            __DIR__,
            CorePlugin::class,
            'virtual',
        ));

        $enabledSystemPlugins = str_explode_filtered($this->config->getAsString('enabledSysPlugins'), ',');
        $enabledComposerOrLocalPlugins = str_explode_filtered($this->config->getAsString('enabledPlugins'), ',');

        // system plugins
        foreach ($this->getInstallablePlugins($enabledSystemPlugins, 'system') as $plugin) {
            $this->loadInstallablePlugin($plugin);
        }
        $this->eventManager->trigger('onSystemPluginsAttached', $this);

        // composer plugins
        foreach ($this->getInstallablePlugins($enabledComposerOrLocalPlugins, 'composer') as $plugin) {
            $this->loadInstallablePlugin($plugin);
        }
        $this->eventManager->trigger('onComposerPluginsAttached', $this);

        // local plugins
        foreach ($this->getInstallablePlugins($enabledComposerOrLocalPlugins, 'local') as $plugin) {
            $this->loadInstallablePlugin($plugin);
        }
        $this->eventManager->trigger('onLocalPluginsAttached', $this);

        $this->loadInstallablePlugin(new InstallablePlugin(
            'LOCAL_EXT',
            __DIR__,
            LocalExtensionsPlugin::class,
            'virtual',
        ));

        $this->loadInstallablePlugin(new InstallablePlugin(
            'APP_EXT',
            __DIR__,
            ApplicationExtensionsPlugin::class,
            'virtual',
        ));

        $this->eventManager->trigger('onPluginsAttached', $this);

        $this->loadInstallablePlugin(new InstallablePlugin(
            'SYS_INFO',
            __DIR__,
            SystemInfoPlugin::class,
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

    private function loadInstallablePlugin(InstallablePlugin $installablePlugin): void
    {
        $plugin = $installablePlugin->createPluginInstance($this->container);

        if ($plugin->apiVersion() < Application::VERSION_API) {
            return; // TODO log info
        }

        foreach ($plugin->consoleCommands() as $command) {
            $this->addConsoleCommand($command);
        }

        foreach ($plugin->interceptingFilters() as $filter) {
            $this->addInterceptingFilter(...$filter);
        }

        foreach ($plugin->applicationMiddlewares() as $appMiddleware) {
            $this->addApplicationMiddleware($appMiddleware);
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

        foreach ($plugin->twigGlobals() as $twigGlobal) {
            $this->addTwigGlobal(...$twigGlobal);
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

        foreach ($plugin->eventListeners() as $event) {
            $this->addEventListener(...$event);
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
    public function getConsoleCommands(): array
    {
        return $this->consoleCommands;
    }

    /**
     * @return array<int, MiddlewareInterface|callable|string>
     */
    public function getApplicationMiddlewares(): array
    {
        return $this->applicationMiddlewares;
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

    private function addConsoleCommand(string $command): void
    {
        $this->consoleCommands[] = $command;
    }

    private function addInterceptingFilter(string $name, callable $callable): void
    {
        $this->filterChainManager->attach($name, $callable);
    }

    /**
     * @param MiddlewareInterface|callable|string $middleware
     * @return void
     */
    private function addApplicationMiddleware($middleware): void
    {
        $this->applicationMiddlewares[] = $middleware;
    }

    /**
     * @param array{string, MiddlewareInterface|callable|string} $routeWithMiddleware
     * @return void
     */
    private function addRouteMiddleware(array $routeWithMiddleware): void
    {
        $this->routeMiddlewares[] = $routeWithMiddleware;
    }

    private function addEventListener(string $name, callable $callable, int $priority = 1): void
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