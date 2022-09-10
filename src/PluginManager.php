<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

use Composer\InstalledVersions;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

final class PluginManager
{
    private EventManager $eventManager;

    private Config $config;

    private string $pluginsPath;

    private array $loadedPlugins;

    private array $pluginPaths;

    private string $sysPluginsPath;

    private ContainerInterface $container;

    private FilterChainManager $filterChainManager;

    private TwigRenderer $twigRenderer;

    private Translator $translator;

    private array $middlewares;

    /**
     * PluginManager constructor.
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
    
    public function init(): void
    {
        $enabledSystemPlugins = explode_list($this->config->get('enabledSysPlugins'));
        $enabledComposerOrLocalPlugins = explode_list($this->config->get('enabledPlugins'));

        $plugins = array_merge(
            $this->getPlugins($enabledSystemPlugins, 'system'),
            $this->getPlugins($enabledComposerOrLocalPlugins, 'plugin')
        );
        
        foreach ($plugins as $plugin) {
            $this->loadPlugin($plugin);
        }
        
        $this->eventManager->trigger('onPluginsAttached', $this);
    }
    
    private function getPlugins(array $enabledPlugins, string $type): array
    {
        $plugins = [];
        foreach ($enabledPlugins as $pluginKey) {
            $pluginConfigPath = sprintf('plugins.%s', $pluginKey);
            $pluginConfig = $this->config->getAsArray($pluginConfigPath);
            if (empty($pluginConfig['pluginName'])
                || empty($pluginConfig['pluginClass'])
                || empty($pluginConfig['pluginPath'])) {
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
            // TODO log info
            return;
        }

        $plugin = $installablePlugin->createPluginInstance($this->container);

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

        $key = $installablePlugin->getKey();
        
        $eventName = sprintf('onPlugin%sAttached', ucfirst($key));
        $this->eventManager->trigger($eventName, $plugin);

        $this->translator->addPath($key, $installablePlugin->getPath() . '/messages');

        $this->loadedPlugins[$key] = $plugin;
        $this->pluginPaths[$key] = $installablePlugin->getPath();
    }

    public function getLoadedPlugins(): array
    {
        return $this->loadedPlugins;
    }

    public function getMiddlewares(): array
    {
        foreach ($this->loadedPlugins as $plugin) {
            if ($plugin instanceof MiddlewareInterface) {
                $this->middlewares[] = $plugin;
            }
        }
        return $this->middlewares;
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
