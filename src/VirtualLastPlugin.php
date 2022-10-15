<?php

declare(strict_types=1);

namespace herbie;

final class VirtualLastPlugin extends Plugin
{
    private Config $config;
    private EventManager $eventManager;
    private FilterChainManager $filterChainManager;
    private MiddlewareDispatcher $middlewareDispatcher;
    private TwigRenderer $twigRenderer;

    public function __construct(
        Config $config,
        EventManager $eventManager,
        FilterChainManager $filterChainManager,
        MiddlewareDispatcher $middlewareDispatcher,
        TwigRenderer $twigRenderer
    ) {
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->filterChainManager = $filterChainManager;
        $this->middlewareDispatcher = $middlewareDispatcher;
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
            'config' => $this->config->flatten(),
            //'constants' => defined_constants('herbie'),
            'events' => $this->eventManager->getEvents(),
            'filters' => $this->filterChainManager->getAllFilters(),
            'middlewares' => $this->middlewareDispatcher->getInfo(),
            'php_classes' => defined_classes('herbie'),
            'php_functions' => defined_functions('herbie'),
            'twig_globals' => $this->transformGlobals($context),
            'twig_filters' => $this->twigRenderer->getFilters(),
            'twig_functions' => $this->twigRenderer->getFunctions(),
            'twig_tests' => $this->twigRenderer->getTests(),
        ];
        return $this->twigRenderer->renderTemplate($template, $context);
    }

    private function transformGlobals(array $context): array
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
}
