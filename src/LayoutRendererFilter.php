<?php

declare(strict_types=1);

namespace Herbie;

class LayoutRendererFilter
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var TwigRenderer
     */
    private $twigRenderer;

    /**
     * ContentRendererFilter constructor.
     * @param Configuration $config
     * @param TwigRenderer $twigRenderer
     */
    public function __construct(Configuration $config, TwigRenderer $twigRenderer)
    {
        $this->config = $config;
        $this->twigRenderer = $twigRenderer;
    }

    /**
     * @param string $content
     * @param array $params
     * @param FilterIterator $chain
     * @return string|null
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function __invoke(string $content, array $params, FilterIterator $chain): ?string
    {
        $this->twigRenderer->init();
        /** @var Page $page */
        $page = $params['page'];
        $extension = trim($this->config['fileExtensions']['layouts']);
        $name = empty($extension) ? $page->getLayout() : sprintf('%s.%s', $page->getLayout(), $extension);
        $content = $this->twigRenderer->renderTemplate($name, $params);
        return $chain->next($content, $params, $chain);
    }
}
