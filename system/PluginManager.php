<?php

declare(strict_types=1);

namespace herbie;

use herbie\events\PluginsInitializedEvent;
use herbie\events\TwigInitializedEvent;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

final class PluginManager
{
    private EventManager $eventManager;

    private Config $config;

    /** @var array<string, InstallablePlugin> */
    private array $loadedPlugins;

    /** @var array<string, string> */
    private array $pluginPaths;

    private ContainerInterface $container;

    private Translator $translator;

    private LoggerInterface $logger;

    /** @var array<int, MiddlewareInterface|callable|string> */
    private array $applicationMiddlewares;

    /** @var array<int, array{string, MiddlewareInterface|callable|string}> */
    private array $routeMiddlewares;

    /** @var string[] */
    private array $consoleCommands;
    private bool $initialized;

    /**
     * PluginManager constructor.
     */
    public function __construct(
        Config $config,
        EventManager $eventManager,
        Translator $translator,
        LoggerInterface $logger,
        ContainerInterface $container // bad but necessary to enable constructor injection of the plugins
    ) {
        $this->config = $config;
        $this->container = $container;
        $this->eventManager = $eventManager;
        $this->initialized = false;
        $this->loadedPlugins = [];
        $this->logger = $logger;
        $this->applicationMiddlewares = [];
        $this->routeMiddlewares = [];
        $this->pluginPaths = [];
        $this->translator = $translator;
    }

    public function init(): void
    {
        if ($this->isInitialized()) {
            return;
        }

        $this->loadInstallablePlugin(
            new InstallablePlugin(
                'CORE',
                __DIR__,
                CorePlugin::class,
                'virtual',
            )
        );

        $enabledSystemPlugins = str_explode_filtered($this->config->getAsString('enabledSysPlugins'), ',');
        $enabledComposerOrLocalPlugins = str_explode_filtered($this->config->getAsString('enabledPlugins'), ',');

        // system plugins
        foreach ($this->getInstallablePlugins($enabledSystemPlugins, 'system') as $plugin) {
            $this->loadInstallablePlugin($plugin);
        }

        // composer plugins
        foreach ($this->getInstallablePlugins($enabledComposerOrLocalPlugins, 'composer') as $plugin) {
            $this->loadInstallablePlugin($plugin);
        }

        // local plugins
        foreach ($this->getInstallablePlugins($enabledComposerOrLocalPlugins, 'local') as $plugin) {
            $this->loadInstallablePlugin($plugin);
        }

        $this->loadInstallablePlugin(
            new InstallablePlugin(
                'LOCAL_EXT',
                __DIR__,
                LocalExtensionsPlugin::class,
                'virtual',
            )
        );

        $this->loadInstallablePlugin(
            new InstallablePlugin(
                'APP_EXT',
                __DIR__,
                ApplicationExtensionsPlugin::class,
                'virtual',
            )
        );

        $this->loadInstallablePlugin(
            new InstallablePlugin(
                'SYS_INFO',
                __DIR__,
                SystemInfoPlugin::class,
                'virtual',
            )
        );

        $this->eventManager->dispatch(new PluginsInitializedEvent($this->loadedPlugins, $this->pluginPaths));

        $this->initialized = true;
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
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

        foreach ($plugin->applicationMiddlewares() as $appMiddleware) {
            $this->addApplicationMiddleware($appMiddleware);
        }

        foreach ($plugin->routeMiddlewares() as $routeMiddleware) {
            $this->addRouteMiddleware($routeMiddleware);
        }

        foreach ($plugin->twigFilters() as $twigFilter) {
            if ($twigFilter instanceof TwigFilter) {
                $this->addTwigFilter($twigFilter);
            } else {
                $this->addTwigFilter(new TwigFilter(...$twigFilter));
            }
        }

        foreach ($plugin->twigGlobals() as $twigGlobal) {
            $this->addTwigGlobal(...$twigGlobal);
        }

        foreach ($plugin->twigFunctions() as $twigFunction) {
            if ($twigFunction instanceof TwigFunction) {
                $this->addTwigFunction($twigFunction);
            } else {
                $this->addTwigFunction(new TwigFunction(...$twigFunction));
            }
        }

        foreach ($plugin->twigTests() as $twigTest) {
            if ($twigTest instanceof TwigTest) {
                $this->addTwigTest($twigTest);
            } else {
                $this->addTwigTest(new TwigTest(...$twigTest));
            }
        }

        foreach ($plugin->eventListeners() as $event) {
            $this->addEventListener(...$event);
        }

        $key = $installablePlugin->getKey();

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

    private function addConsoleCommand(string $command): void
    {
        $this->consoleCommands[] = $command;
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

    private function addTwigFilter(TwigFilter $filter): void
    {
        $closure = function (TwigInitializedEvent $event) use ($filter) {
            $event->getEnvironment()->addFilter($filter);
        };
        $this->eventManager->addListener(TwigInitializedEvent::class, $closure);
    }

    /**
     * @param mixed $mixed
     */
    private function addTwigGlobal(string $name, $mixed): void
    {
        $closure = function (TwigInitializedEvent $event) use ($name, $mixed) {
            $event->getEnvironment()->addGlobal($name, $mixed);
        };
        $this->eventManager->addListener(TwigInitializedEvent::class, $closure);
    }

    private function addTwigFunction(TwigFunction $function): void
    {
        $closure = function (TwigInitializedEvent $event) use ($function) {
            $event->getEnvironment()->addFunction($function);
        };
        $this->eventManager->addListener(TwigInitializedEvent::class, $closure);
    }

    private function addTwigTest(TwigTest $test): void
    {
        $closure = function (TwigInitializedEvent $event) use ($test) {
            $event->getEnvironment()->addTest($test);
        };
        $this->eventManager->addListener(TwigInitializedEvent::class, $closure);
    }

    private function addEventListener(string $name, callable $callable, int $priority = 1): void
    {
        $this->eventManager->addListener($name, $callable, $priority);
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
}
