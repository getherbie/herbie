<?php

declare(strict_types=1);

namespace herbie;

final class VirtualLastPlugin extends Plugin
{
    private Config $config;
    private EventManager $eventManager;
    private FilterChainManager $filterChainManager;
    private MiddlewareDispatcher $middlewareDispatcher;
    private PluginManager $pluginManager;
    private TwigRenderer $twigRenderer;

    public function __construct(
        Config $config,
        EventManager $eventManager,
        FilterChainManager $filterChainManager,
        MiddlewareDispatcher $middlewareDispatcher,
        PluginManager $pluginManager,
        TwigRenderer $twigRenderer
    ) {
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->filterChainManager = $filterChainManager;
        $this->middlewareDispatcher = $middlewareDispatcher;
        $this->pluginManager = $pluginManager;
        $this->twigRenderer = $twigRenderer;
    }

    public function twigFunctions(): array
    {
        return [
            ['herbie_info', [$this, 'herbieInfo'], ['is_safe' => ['html'], 'needs_context' => true]],
        ];
    }

    public function herbieInfo(array $context, string $template = '@snippet/herbie_info.twig'): string
    {
        $context = [
            'config' => $this->getConfig(),
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
        return $this->twigRenderer->renderTemplate($template, $context);
    }

    private function getConfig(): array
    {
        return $this->config->flatten();
    }

    private function getEvents(): array
    {
        $items = [];
        foreach ($this->eventManager->getEvents() as $eventName => $eventsWithPriority) {
            foreach ($eventsWithPriority as $priority => $events) {
                foreach ($events as $event) {
                    foreach ($event as $e) {
                        $items[] = array_merge(
                            [$eventName, $priority],
                            get_callable_name($e)
                        );
                    }
                }
            }
        }
        return $items;
    }

    private function getFilters(): array
    {
        $items = [];
        foreach ($this->filterChainManager->getAllFilters() as $category => $filterChain) {
            $filters = $filterChain->getFilters()->items();
            foreach ($filters as $filter) {
                $items[] = [
                    $category,
                    get_callable_name($filter)
                ];
            }
        }
        return $items;
    }

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

    private function getTwigGlobalsFromContext(array $context): array
    {
        $globals = [];
        foreach ($context as $string => $mixed) {
            if (is_string($mixed)) {
                $value = $mixed;
                $type = 'string';
            } elseif (is_object($mixed)) {
                $value = get_class($mixed);
                $type = 'class';
            } else {
                $value = json_encode($mixed);
                $type = 'unknown';
            }
            $globals[] = [$string, $value, $type];
        }
        return $globals;
    }

    private function getTwigFilters(): array
    {
        $items = [];
        foreach ($this->twigRenderer->getTwigEnvironment()->getFilters() as $f) {
            $items[] = [
                $f->getName(),
                get_callable_name($f->getCallable())
            ];
        }
        return $items;
    }

    private function getTwigFunctions(): array
    {
        $items = [];
        foreach ($this->twigRenderer->getTwigEnvironment()->getFunctions() as $f) {
            $items[] = [
                $f->getName(),
                get_callable_name($f->getCallable())
            ];
        }
        return $items;
    }

    private function getTwigTests(): array
    {
        $items = [];
        foreach ($this->twigRenderer->getTwigEnvironment()->getTests() as $f) {
            $callable = $f->getCallable() ?? $f->getName();
            $items[] = [
                $f->getName(),
                get_callable_name($callable)
            ];
        }
        return $items;
    }
}
