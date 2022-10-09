<?php

declare(strict_types=1);

namespace herbie;

use Psr\Http\Server\MiddlewareInterface;

final class VirtualLastPlugin extends Plugin
{
    private Config $config;
    private MiddlewareDispatcher $middlewareDispatcher;
    private TwigRenderer $twigRenderer;

    public function __construct(Config $config, MiddlewareDispatcher $middlewareDispatcher, TwigRenderer $twigRenderer)
    {
        $this->config = $config;
        $this->middlewareDispatcher = $middlewareDispatcher;
        $this->twigRenderer = $twigRenderer;
    }

    public function twigFunctions(): array
    {
        return [
            ['herbie_info', [$this, 'herbieInfo'], ['is_safe' => ['html']]],
        ];
    }

    public function herbieInfo(string $template = '@snippet/herbie_info.twig'): string
    {
        $context = [
            'config' => $this->config->flatten(),
            'constants' => defined_constants('herbie'),
            'classes' => defined_classes('herbie'),
            'functions' => defined_functions('herbie'),
            'middlewares' => $this->middlewareDispatcher->getInfo(),
        ];
        return $this->twigRenderer->renderTemplate($template, $context);
    }
}
