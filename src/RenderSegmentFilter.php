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
    /**
     * @var TwigRenderer
     */
    private $twigRenderer;

    /**
     * ContentRendererFilter constructor.
     * @param TwigRenderer $twigRenderer
     */
    public function __construct(TwigRenderer $twigRenderer)
    {
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
        if (!empty($page->getTwig())) {
            $content = $this->twigRenderer->renderString((string)$content, $params);
        }
        return $chain->next($content, $params, $chain);
    }
}
