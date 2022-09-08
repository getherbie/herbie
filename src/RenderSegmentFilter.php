<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

class RenderSegmentFilter
{
    private TwigRenderer $twigRenderer;

    /**
     * ContentRendererFilter constructor.
     */
    public function __construct(TwigRenderer $twigRenderer)
    {
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
        if (!empty($page->getTwig())) {
            $content = $this->twigRenderer->renderString($content, $params);
        }
        return $chain->next($content, $params, $chain);
    }
}
