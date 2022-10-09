<?php

declare(strict_types=1);

namespace herbie;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class PluginManager
{
    private EventManager $eventManager;

    private Config $config;

    private array $loadedPlugins;

    private array $pluginPaths;

    private ContainerInterface $container;

    private FilterChainManager $filterChainManager;

    private Translator $translator;

    private LoggerInterface $logger;

    private array $appMiddlewares;

    private array $routeMiddlewares;

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
            __DIR__ . '/VirtualCorePlugin.php',
            'virtual',
        ));

        $enabledSystemPlugins = explode_list($this->config->get('enabledSysPlugins'));
        $enabledComposerOrLocalPlugins = explode_list($this->config->get('enabledPlugins'));

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
            __DIR__ . '/VirtualLocalPlugin.php',
            'virtual',
        ));

        $this->loadPlugin(new InstallablePlugin(
            'virtual_app_plugin',
            __DIR__,
            __DIR__ . '/VirtualAppPlugin.php',
            'virtual',
        ));

        $this->eventManager->trigger('onPluginsAttached', $this);

        $this->loadPlugin(new InstallablePlugin(
            'virtual_last_plugin',
            __DIR__,
            __DIR__ . '/VirtualLastPlugin.php',
            'virtual',
        ));
    }

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
        if (!$installablePlugin->classPathExists()) {
            return; // TODO log info
        }

        $plugin = $installablePlugin->createPluginInstance($this->container);

        if ($plugin->apiVersion() < HERBIE_API_VERSION) {
            return; // TODO log info
        }

        foreach ($plugin->filters() as $filter) {
            $this->attachFilter(...$filter);
        }

        foreach ($plugin->appMiddlewares() as $appMiddleware) {
            $this->appMiddlewares[] = $appMiddleware;
        }

        foreach ($plugin->routeMiddlewares() as $routeMiddleware) {
            $this->routeMiddlewares[] = $routeMiddleware;
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
            $this->attachListener(...$event);
        }

        $key = $installablePlugin->getKey();

        $eventName = sprintf('onPlugin%sAttached', ucfirst($key));
        $this->eventManager->trigger($eventName, $plugin);

        $this->translator->addPath($key, $installablePlugin->getPath() . '/messages');

        $this->loadedPlugins[$key] = $plugin;
        $this->pluginPaths[$key] = $installablePlugin->getPath();

        $message = sprintf(
            'Plugin %s with type %s installed successfully',
            $installablePlugin->getKey(),
            $installablePlugin->getType(),
        );
        $this->logger->debug($message);
    }

    public function getLoadedPlugins(): array
    {
        return $this->loadedPlugins;
    }

    public function getAppMiddlewares(): array
    {
        return $this->appMiddlewares;
    }

    public function getRouteMiddlewares(): array
    {
        return $this->routeMiddlewares;
    }

    public function getPluginPaths(): array
    {
        return $this->pluginPaths;
    }

    private function attachFilter(string $name, callable $callable): void
    {
        $this->filterChainManager->attach($name, $callable);
    }

    private function attachListener(string $name, callable $callable, int $priority = 1): void
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
