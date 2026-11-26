<?php

declare(strict_types=1);

namespace herbie;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use herbie\events\PluginsInitializedEvent;
use herbie\events\TwigInitializedEvent;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

final class PluginManager
{
    /** @var array<string, InstallablePlugin> */
    private array $loadedPlugins;

    /** @var array<string, string> */
    private array $pluginPaths;

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
        private readonly Config $config,
        private readonly EventManager $eventManager,
        private readonly Translator $translator,
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container, // bad but necessary to enable constructor injection of the plugins
    ) {
        $this->initialized = false;
        $this->loadedPlugins = [];
        $this->applicationMiddlewares = [];
        $this->routeMiddlewares = [];
        $this->consoleCommands = [];
        $this->pluginPaths = [];
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
            ),
        );

        foreach ($this->resolveInstallablePlugins() as $plugin) {
            $this->loadInstallablePlugin($plugin);
        }

        $this->loadInstallablePlugin(
            new InstallablePlugin(
                'LOCAL_EXT',
                __DIR__,
                LocalExtensionsPlugin::class,
                'virtual',
            ),
        );

        $this->loadInstallablePlugin(
            new InstallablePlugin(
                'APP_EXT',
                __DIR__,
                ApplicationExtensionsPlugin::class,
                'virtual',
            ),
        );

        $this->loadInstallablePlugin(
            new InstallablePlugin(
                'SYS_INFO',
                __DIR__,
                SystemInfoPlugin::class,
                'virtual',
            ),
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
            $this->logger->warning(sprintf(
                'Plugin %s requires API version %d, but Herbie provides API version %d. The plugin is not loaded.',
                $installablePlugin->getKey(),
                $plugin->apiVersion(),
                Application::VERSION_API,
            ));
            return;
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
        $closure = function (TwigInitializedEvent $event) use ($filter): void {
            $event->getEnvironment()->addFilter($filter);
        };
        $this->eventManager->addListener(TwigInitializedEvent::class, $closure);
    }

    /**
     * @param mixed $mixed
     */
    private function addTwigGlobal(string $name, $mixed): void
    {
        $closure = function (TwigInitializedEvent $event) use ($name, $mixed): void {
            $event->getEnvironment()->addGlobal($name, $mixed);
        };
        $this->eventManager->addListener(TwigInitializedEvent::class, $closure);
    }

    private function addTwigFunction(TwigFunction $function): void
    {
        $closure = function (TwigInitializedEvent $event) use ($function): void {
            $event->getEnvironment()->addFunction($function);
        };
        $this->eventManager->addListener(TwigInitializedEvent::class, $closure);
    }

    private function addTwigTest(TwigTest $test): void
    {
        $closure = function (TwigInitializedEvent $event) use ($test): void {
            $event->getEnvironment()->addTest($test);
        };
        $this->eventManager->addListener(TwigInitializedEvent::class, $closure);
    }

    private function addEventListener(string $name, callable $callable, int $priority = 1): void
    {
        $this->eventManager->addListener($name, $callable, $priority);
    }

    /**
     * Resolves the names in the config option "enabledPlugins" to installable plugins.
     * It does not matter whether a plugin is a system, composer or local plugin.
     * The plugins are returned in the order of the list.
     *
     * @return InstallablePlugin[]
     */
    public function resolveInstallablePlugins(): array
    {
        $plugins = [];
        foreach (str_explode_filtered($this->config->getAsString('enabledPlugins'), ',') as $pluginKey) {
            $pluginConfig = $this->config->getAsArray(sprintf('plugins.%s', $pluginKey));
            if (
                empty($pluginConfig['pluginName'])
                || empty($pluginConfig['pluginClass'])
                || empty($pluginConfig['pluginPath'])
            ) {
                $this->logger->warning(sprintf(
                    'Plugin %s is enabled, but it was not found or its config is incomplete. The plugin is not loaded.',
                    $pluginKey,
                ));
                continue;
            }
            // plugins that are not autoloadable, i.e. local plugins, are loaded from their file
            $pluginFile = $pluginConfig['pluginFile'] ?? null;
            if (is_string($pluginFile) && !class_exists($pluginConfig['pluginClass']) && is_file($pluginFile)) {
                require_once $pluginFile;
            }
            $this->assertRequiredPackages($pluginKey, $pluginConfig['pluginClass']);
            $plugins[] = new InstallablePlugin(
                $pluginConfig['pluginName'],
                $pluginConfig['pluginPath'],
                $pluginConfig['pluginClass'],
                (string)($pluginConfig['location'] ?? ''),
            );
        }

        return $plugins;
    }

    /**
     * Checks the packages that a plugin declares with the key "requires" of the attribute Plugin.
     *
     * @throws SystemException if a package is missing or does not match the version constraint
     */
    private function assertRequiredPackages(string $pluginKey, string $className): void
    {
        if (!class_exists($className)) {
            return;
        }
        $attributes = (new ReflectionClass($className))->getAttributes(Plugin::class);
        if ($attributes === []) {
            return;
        }
        foreach ($attributes[0]->newInstance()->requires as $key => $value) {
            $package = is_int($key) ? $value : $key;
            $constraint = is_int($key) ? null : $value;

            if (!InstalledVersions::isInstalled($package)) {
                throw new SystemException(sprintf(
                    'Plugin %s requires the package %s. Install it with: composer require %s',
                    $pluginKey,
                    $package,
                    $constraint === null ? $package : sprintf('"%s:%s"', $package, $constraint),
                ), 500);
            }

            if ($constraint !== null && !InstalledVersions::satisfies(new VersionParser(), $package, $constraint)) {
                throw new SystemException(sprintf(
                    'Plugin %s requires the package %s in version %s, but version %s is installed. Update it with: composer require "%s:%s"',
                    $pluginKey,
                    $package,
                    $constraint,
                    (string)InstalledVersions::getPrettyVersion($package),
                    $package,
                    $constraint,
                ), 500);
            }
        }
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
