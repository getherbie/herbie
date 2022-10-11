<?php

declare(strict_types=1);

namespace herbie;

final class VirtualCorePlugin extends Plugin
{
    private Config $config;
    private TwigRenderer $twigRenderer;

    public function __construct(Config $config, TwigRenderer $twigRenderer)
    {
        $this->config = $config;
        $this->twigRenderer = $twigRenderer;
    }

    public function filters(): array
    {
        return [
            ['renderLayout', [$this, 'renderLayout']],
            ['renderSegment', [$this, 'renderSegment']]
        ];
    }

    public function twigFunctions(): array
    {
        return [
            ['herbie_debug', [$this, 'herbieDebug']],
        ];
    }

    public function herbieDebug(): bool
    {
        return Application::isDebug();
    }

    public function renderSegment(string $context, array $params, FilterInterface $filter): string
    {
        /** @var Page $page */
        $page = $params['page'];
        if (!empty($page->getTwig())) {
            $context = $this->twigRenderer->renderString($context, $params);
        }
        return $filter->next($context, $params, $filter);
    }

    public function renderLayout(string $context, array $params, FilterInterface $filter): string
    {
        /** @var Page $page */
        $page = $params['page'];
        $extension = trim($this->config->getAsString('fileExtensions.layouts'));
        $name = empty($extension) ? $page->getLayout() : sprintf('%s.%s', $page->getLayout(), $extension);
        $context = $this->twigRenderer->renderTemplate($name, $params);
        return $filter->next($context, $params, $filter);
    }
}
