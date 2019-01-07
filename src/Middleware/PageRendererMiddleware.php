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

use Herbie\Config;
use Herbie\Environment;
use Herbie\Menu\MenuList;
use Herbie\Menu\MenuTree;
use Herbie\Menu\RootPath;
use Herbie\Page;
use Herbie\Repository\DataRepositoryInterface;
use Herbie\Site;
use Herbie\StringValue;
use Herbie\TwigRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Tebe\HttpFactory\HttpFactory;
use Zend\EventManager\EventManagerInterface;

class PageRendererMiddleware implements MiddlewareInterface
{
    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var HttpFactory
     */
    private $httpFactory;

    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    private $twigRenderer;

    private $config;
    /**
     * @var DataRepositoryInterface
     */
    private $dataRepository;
    /**
     * @var MenuList
     */
    private $menuList;
    /**
     * @var MenuTree
     */
    private $menuTree;
    /**
     * @var RootPath
     */
    private $menuRootPath;

    /**
     * PageRendererMiddleware constructor.
     * @param CacheInterface $cache
     * @param Environment $environment
     * @param HttpFactory $httpFactory
     * @param EventManagerInterface $eventManager
     * @param TwigRenderer $twigRenderer
     * @param Config $config
     * @param DataRepositoryInterface $dataRepository
     * @param MenuList $menuList
     * @param MenuTree $menuTree
     * @param RootPath $menuRootPath
     */
    public function __construct(
        CacheInterface $cache,
        Environment $environment,
        HttpFactory $httpFactory,
        EventManagerInterface $eventManager,
        TwigRenderer $twigRenderer,
        Config $config,
        DataRepositoryInterface $dataRepository,
        MenuList $menuList,
        MenuTree $menuTree,
        RootPath $menuRootPath
    ) {
        $this->cache = $cache;
        $this->environment = $environment;
        $this->httpFactory = $httpFactory;
        $this->eventManager = $eventManager;
        $this->twigRenderer = $twigRenderer;
        $this->config = $config;
        $this->dataRepository = $dataRepository;
        $this->menuList = $menuList;
        $this->menuTree = $menuTree;
        $this->menuRootPath = $menuRootPath;
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
    private function renderPage(Page $page): ResponseInterface
    {
        // initialize as late as possible
        $this->twigRenderer->init();

        $rendered = null;

        $cacheId = 'page-' . $this->environment->getRoute();
        if (empty($page->nocache)) {
            $rendered = $this->cache->get($cacheId);
        }

        if (null === $rendered) {
            $context = [
                'site' => new Site(
                    $this->config,
                    $this->dataRepository,
                    $this->menuList,
                    $this->menuTree,
                    $this->menuRootPath
                ),
                'page' => $page
            ];

            // Render segments
            $renderedSegments = [];
            foreach ($page->getSegments() as $segmentId => $content) {
                if (empty($page->twig)) {
                    $renderedContent = new StringValue($content);
                } else {
                    $renderedContent = new StringValue($this->twigRenderer->renderString($content, $context));
                }
                $this->eventManager->trigger('onRenderContent', $renderedContent, $page->getData());
                $renderedSegments[$segmentId] = $renderedContent->get();
            }

            $content = new StringValue();

            if (empty($page->layout)) {
                $content->set(implode('', $renderedSegments));
            } else {
                $extension = trim($this->config->get('layouts.extension'));
                $name = empty($extension) ? $page->layout : sprintf('%s.%s', $page->layout, $extension);
                $content->set($this->twigRenderer->renderTemplate($name, array_merge([
                    'content' => $renderedSegments
                ], $context)));
                $this->eventManager->trigger('onRenderLayout', $content, ['page' => $page]);
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
