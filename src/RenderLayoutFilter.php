<?php

/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

final class RenderLayoutFilter
{
    private Config $config;

    private TwigRenderer $twigRenderer;

    /**
     * ContentRendererFilter constructor.
     */
    public function __construct(Config $config, TwigRenderer $twigRenderer)
    {
        $this->config = $config;
        $this->twigRenderer = $twigRenderer;
    }

    /**
     * @throws \Throwable
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function __invoke(string $content, array $params, FilterIterator $chain): ?string
    {
        $this->twigRenderer->init();
        /** @var Page $page */
        $page = $params['page'];
        $extension = trim($this->config->get('fileExtensions.layouts'));
        $name = empty($extension) ? $page->getLayout() : sprintf('%s.%s', $page->getLayout(), $extension);
        $content = $this->twigRenderer->renderTemplate($name, $params);
        return $chain->next($content, $params, $chain);
    }
}
