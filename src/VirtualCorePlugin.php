<?php

declare(strict_types=1);

namespace herbie;

final class VirtualCorePlugin extends Plugin
{
    private TwigRenderer $twigRenderer;
    private string $layoutFileExtension;
    private bool $enableTwigInLayoutFilter;
    private bool $enableTwigInSegmentFilter;

    public function __construct(Config $config, TwigRenderer $twigRenderer)
    {
        $this->enableTwigInLayoutFilter = $config->getAsBool('components.virtualCorePlugin.enableTwigInLayoutFilter');
        $this->enableTwigInSegmentFilter = $config->getAsBool('components.virtualCorePlugin.enableTwigInSegmentFilter');
        $this->layoutFileExtension = trim($config->getAsString('fileExtensions.layouts'));
        $this->twigRenderer = $twigRenderer;
    }

    public function commands(): array
    {
        return [
            ClearCacheCommand::class
        ];
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

    public function twigGlobals(): array
    {
        return [];
    }

    public function herbieDebug(): bool
    {
        return Application::isDebug();
    }

    /**
     * @param array{page: Page, routeParams: array<string, mixed>} $params
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function renderSegment(string $context, array $params, FilterInterface $filter): string
    {
        /** @var Page $page */
        $page = $params['page'];
        if ($this->enableTwigInSegmentFilter && !empty($page->getTwig())) {
            $context = $this->twigRenderer->renderString($context, $params);
        }
        return $filter->next($context, $params, $filter);
    }

    /**
     * @param array{content: array<string, string>, page: Page, routeParams: array<string, mixed>} $params
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function renderLayout(string $_, array $params, FilterInterface $filter): string
    {
        /** @var Page $page */
        $page = $params['page'];
        if (strlen($this->layoutFileExtension) > 0) {
            $name = sprintf('%s.%s', $page->getLayout(), $this->layoutFileExtension);
        } else {
            $name = $page->getLayout();
        }
        if ($this->enableTwigInLayoutFilter) {
            $context = $this->twigRenderer->renderTemplate($name, $params);
        } else {
            $context = join('', $params['content']);
        }
        return $filter->next($context, $params, $filter);
    }
}
