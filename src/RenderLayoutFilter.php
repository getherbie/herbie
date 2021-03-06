<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

class RenderLayoutFilter
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var TwigRenderer
     */
    private $twigRenderer;

    /**
     * ContentRendererFilter constructor.
     * @param Config $config
     * @param TwigRenderer $twigRenderer
     */
    public function __construct(Config $config, TwigRenderer $twigRenderer)
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
