<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie\Middleware;

use Herbie\Environment;
use Herbie\Page;
use Herbie\PluginManager;
use Herbie\StringValue;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Tebe\HttpFactory\HttpFactory;

class PageRendererMiddleware implements MiddlewareInterface
{
    protected $cache;
    protected $environment;
    protected $httpFactory;
    protected $pluginManager;

    /**
     * PageRendererMiddleware constructor.
     * @param CacheInterface $cache
     * @param Environment $environment
     * @param HttpFactory $httpFactory
     * @param PluginManager $pluginManager
     */
    public function __construct(CacheInterface $cache, Environment $environment, HttpFactory $httpFactory, PluginManager $pluginManager)
    {
        $this->cache = $cache;
        $this->environment = $environment;
        $this->httpFactory = $httpFactory;
        $this->pluginManager = $pluginManager;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $page = $request->getAttribute(Page::class, false);

        if (!$page) {
            $message = sprintf('Server request attribute "%s" doesn\'t exist', Page::class);
            throw new \InvalidArgumentException($message);
        }

        return $this->renderPage($page);
    }

    /**
     * @param Page $page
     * @return ResponseInterface
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function renderPage(Page $page): ResponseInterface
    {
        $rendered = null;

        $cacheId = 'page-' . $this->environment->getRoute();
        if (empty($page->nocache)) {
            $rendered = $this->cache->get($cacheId);
        }

        if (null === $rendered) {
            $content = new StringValue();

            try {
                if (empty($page->layout)) {
                    $content = $page->getSegment('default');
                    $this->pluginManager->trigger('onRenderContent', $content, $page->getData());
                } else {
                    $this->pluginManager->trigger('onRenderLayout', $content, ['page' => $page]);
                }
            } catch (\Throwable $t) {
                $page->setError($t);
                $this->pluginManager->trigger('onRenderLayout', $content, ['page' => $page]);
            }

            if (empty($page->nocache)) {
                $this->cache->set($cacheId, $content->get());
            }
            $rendered = $content->get();
        }

        $response = $this->httpFactory->createResponse($page->getStatusCode());
        $response->getBody()->write($rendered);
        $response->withHeader('Content-Type', $page->content_type);

        return $response;
    }
}
