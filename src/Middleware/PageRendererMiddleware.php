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
use Herbie\EventManager;
use Herbie\Menu\MenuList;
use Herbie\Menu\MenuTrail;
use Herbie\Menu\MenuTree;
use Herbie\Page;
use Herbie\Repository\DataRepositoryInterface;
use Herbie\StringValue;
use Herbie\TwigRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Tebe\HttpFactory\HttpFactory;

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

    /**
     * @var TwigRenderer
     */
    private $twigRenderer;

    /**
     * @var Config
     */
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
     * @var MenuTrail
     */
    private $menuTrail;

    /**
     * PageRendererMiddleware constructor.
     * @param CacheInterface $cache
     * @param Environment $environment
     * @param HttpFactory $httpFactory
     * @param EventManager $eventManager
     * @param TwigRenderer $twigRenderer
     * @param Config $config
     * @param DataRepositoryInterface $dataRepository
     * @param MenuList $menuList
     * @param MenuTree $menuTree
     * @param MenuTrail $menuTrail
     */
    public function __construct(
        CacheInterface $cache,
        Environment $environment,
        HttpFactory $httpFactory,
        EventManager $eventManager,
        TwigRenderer $twigRenderer,
        Config $config,
        DataRepositoryInterface $dataRepository,
        MenuList $menuList,
        MenuTree $menuTree,
        MenuTrail $menuTrail
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
        $this->menuTrail = $menuTrail;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Page $page */
        $page = $request->getAttribute('HERBIE_PAGE', null);

        /** @var array $routeParams */
        $routeParams = $request->getAttribute('HERBIE_ROUTE_PARAMS', []);

        if (!$page) {
            $message = sprintf('Server request attribute "%s" doesn\'t exist', Page::class);
            throw new \InvalidArgumentException($message);
        }

        return $this->renderPage($page, $routeParams);
    }

    /**
     * @param Page $page
     * @param array $routeParams
     * @return ResponseInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function renderPage(Page $page, array $routeParams): ResponseInterface
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
                'page' => $page,
                'routeParams' => $routeParams
            ];

            // Render segments
            $renderedSegments = [];
            foreach ($page->getSegments() as $segmentId => $content) {
                if (empty($page->twig)) {
                    $renderedContent = new StringValue($content);
                } else {
                    $renderedContent = new StringValue($this->twigRenderer->renderString($content, $context));
                }
                $this->eventManager->trigger('onRenderContent', $renderedContent, $page->toArray());
                $renderedSegments[$segmentId] = $renderedContent->get();
            }

            $content = new StringValue();

            if (empty($page->layout)) {
                $content->set(implode('', $renderedSegments));
            } else {
                $extension = trim($this->config['fileExtensions']['layouts']);
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

        $response = $this->httpFactory->createResponse(200);
        $response->getBody()->write($rendered);
        $response->withHeader('Content-Type', $page->content_type);

        return $response;
    }
}
