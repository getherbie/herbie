<?php

declare(strict_types=1);

namespace herbie;

final class SystemInfoPlugin extends Plugin
{
    private Alias $alias;
    private Config $config;
    private EventManager $eventManager;
    private FilterChainManager $filterChainManager;
    private MiddlewareDispatcher $middlewareDispatcher;
    private PluginManager $pluginManager;
    private TwigRenderer $twigRenderer;
    private string $appPath;

    public function __construct(
        Alias $alias,
        Config $config,
        EventManager $eventManager,
        FilterChainManager $filterChainManager,
        MiddlewareDispatcher $middlewareDispatcher,
        PluginManager $pluginManager,
        TwigRenderer $twigRenderer
    ) {
        $this->alias = $alias;
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->filterChainManager = $filterChainManager;
        $this->middlewareDispatcher = $middlewareDispatcher;
        $this->pluginManager = $pluginManager;
        $this->twigRenderer = $twigRenderer;
        $this->appPath = str_untrailing_slash($config->getAsString('paths.app'));
    }

    public function twigFunctions(): array
    {
        return [
            ['herbie_info', [$this, 'herbieInfo'], ['is_safe' => ['html'], 'needs_context' => true]],
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function herbieInfo(array $context, string $template = '@snippet/herbie_info.twig'): string
    {
        $info = [
            'aliases' => $this->getAlias(),
            'commands' => $this->getCommands(),
            'configs' => $this->getConfig(),
            'events' => $this->getEvents(),
            'filters' => $this->getFilters(),
            'middlewares' => $this->getMiddlewares(),
            'php_classes' => defined_classes('herbie'),
            'php_functions' => defined_functions('herbie'),
            'plugins' => $this->getPlugins(),
            'twig_globals' => $this->getTwigGlobalsFromContext($context),
            'twig_filters' => $this->getTwigFilters(),
            'twig_functions' => $this->getTwigFunctions(),
            'twig_tests' => $this->getTwigTests(),
        ];
        return $this->twigRenderer->renderTemplate($template, $info);
    }

    private function getAlias(): array
    {
        $items = [];
        foreach ($this->alias->getAll() as $key => $value) {
            $items[] = [$key, $value];
        }
        return $items;
    }

    /**
     * @return string[]
     */
    private function getCommands(): array
    {
        $items = [];
        foreach ($this->pluginManager->getConsoleCommands() as $command) {
            $items[] = $command;
        }
        return $items;
    }

    /**
     * @return array<int, array{string, string, mixed}>
     */
    private function getConfig(): array
    {
        $configs = [];
        foreach ($this->config->flatten() as $key => $value) {
            $configs[] = [
                $key,
                gettype($value),
                $this->filterValue($value)
            ];
        }
        return $configs;
    }

    /**
     * @return array<int, string[]>
     */
    private function getEvents(): array
    {
        $items = [];
        foreach ($this->eventManager->getEvents() as $eventName => $eventsWithPriority) {
            foreach ($eventsWithPriority as $priority => $events) {
                foreach ($events as $event) {
                    $items[] = array_merge(
                        [$eventName, (string)$priority],
                        get_callable_name($event)
                    );
                }
            }
        }
        return $items;
    }

    /**
     * @return array<int, string[]>
     */
    private function getFilters(): array
    {
        $items = [];
        foreach ($this->filterChainManager->getAllFilters() as $category => $filterChain) {
            $filters = $filterChain->getFilters()->items();
            foreach ($filters as $filter) {
                $items[] = [
                    $category,
                    ...get_callable_name($filter)
                ];
            }
        }
        return $items;
    }

    /**
     * @return array<int, string[]>
     */
    private function getMiddlewares(): array
    {
        $info = [];
        foreach ($this->middlewareDispatcher->getMiddlewares() as $middleware) {
            if (is_array($middleware) && (is_string($middleware[0]))) {
                $type = 'ROUTE';
                $callable = get_callable_name($middleware[1]);
            } else {
                $type = 'APP';
                $callable = get_callable_name($middleware);
            }
            $info[] = [
                $type,
                $callable[0],
                $callable[1],
            ];
        }
        return $info;
    }

    /**
     * @return array<int, string[]>
     */
    private function getPlugins(): array
    {
        $plugins = [];
        foreach ($this->pluginManager->getLoadedPlugins() as $plugin) {
            $plugins[] = [
                $plugin->getKey(),
                $plugin->getType(),
                $plugin->getClassName()
            ];
        }
        return $plugins;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, mixed>
     */
    private function getTwigGlobalsFromContext(array $context): array
    {
        $globals = [];
        foreach ($context as $string => $mixed) {
            if (is_scalar($mixed)) {
                $value = $mixed;
                $type = gettype($mixed);
            } elseif (is_object($mixed)) {
                $value = get_class($mixed);
                $type = 'class';
            } else {
                $value = json_encode($mixed);
                $type = gettype($mixed);
            }
            $globals[] = [$string, $value, $type];
        }
        return $globals;
    }

    /**
     * @return array<int, string[]>
     */
    private function getTwigFilters(): array
    {
        $items = [];
        foreach ($this->twigRenderer->getTwigEnvironment()->getFilters() as $f) {
            $callable = $f->getCallable() ?? $f->getName();
            $items[] = [
                $f->getName(),
                ...get_callable_name($callable)
            ];
        }
        return $items;
    }

    /**
     * @return array<int, string[]>
     */
    private function getTwigFunctions(): array
    {
        $items = [];
        foreach ($this->twigRenderer->getTwigEnvironment()->getFunctions() as $f) {
            $callable = $f->getCallable() ?? $f->getName();
            $items[] = [
                $f->getName(),
                ...get_callable_name($callable)
            ];
        }
        return $items;
    }

    /**
     * @return array<int, string[]>
     */
    private function getTwigTests(): array
    {
        $items = [];
        foreach ($this->twigRenderer->getTwigEnvironment()->getTests() as $f) {
            $callable = $f->getCallable() ?? $f->getName();
            $items[] = [
                $f->getName(),
                ...get_callable_name($callable)
            ];
        }
        return $items;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function filterValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $replaceIfEquals = [$this->appPath => '/'];
        foreach ($replaceIfEquals as $k => $v) {
            if ($k === $value) {
                $value = $v;
            }
        }

        $stripFromBeginning = [$this->appPath];
        foreach ($stripFromBeginning as $v) {
            if (strpos($value, $v) === 0) {
                $value = substr($value, strlen($v));
            }
        }

        return $value;
    }
}
