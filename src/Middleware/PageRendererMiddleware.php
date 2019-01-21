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
use Herbie\Exception\SystemException;
use Herbie\Page\Page;
use Herbie\Page\PageList;
use Herbie\Page\PageTrail;
use Herbie\Page\PageTree;
use Herbie\Repository\DataRepositoryInterface;
use Herbie\StringValue;
use Herbie\Twig\TwigRenderer;
use Herbie\Url\UrlGenerator;
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
     * @var PageList
     */
    private $pageList;

    /**
     * @var PageTree
     */
    private $pageTree;

    /**
     * @var PageTrail
     */
    private $pageTrail;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * PageRendererMiddleware constructor.
     * @param CacheInterface $cache
     * @param Environment $environment
     * @param HttpFactory $httpFactory
     * @param EventManager $eventManager
     * @param TwigRenderer $twigRenderer
     * @param Config $config
     * @param DataRepositoryInterface $dataRepository
     * @param PageList $pageList
     * @param PageTree $pageTree
     * @param PageTrail $pageTrail
     */
    public function __construct(
        CacheInterface $cache,
        Environment $environment,
        HttpFactory $httpFactory,
        EventManager $eventManager,
        TwigRenderer $twigRenderer,
        Config $config,
        DataRepositoryInterface $dataRepository,
        PageList $pageList,
        PageTree $pageTree,
        PageTrail $pageTrail,
        UrlGenerator $urlGenerator
    ) {
        $this->cache = $cache;
        $this->environment = $environment;
        $this->httpFactory = $httpFactory;
        $this->eventManager = $eventManager;
        $this->twigRenderer = $twigRenderer;
        $this->config = $config;
        $this->dataRepository = $dataRepository;
        $this->pageList = $pageList;
        $this->pageTree = $pageTree;
        $this->pageTrail = $pageTrail;
        $this->urlGenerator = $urlGenerator;
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
        $page = $request->getAttribute(PageResolverMiddleware::REQUEST_ATTRIBUTE_PAGE, null);

        if (is_null($page)) {
            $message = sprintf(
                'Server request attribute "%s" not set',
                PageResolverMiddleware::REQUEST_ATTRIBUTE_PAGE
            );
            throw new \InvalidArgumentException($message);
        }

        /** @var array $routeParams */
        $routeParams = $request->getAttribute(PageResolverMiddleware::REQUEST_ATTRIBUTE_ROUTE_PARAMS, []);

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
        $redirect = $page->getRedirect();

        if (!empty($redirect)) {
            $response = $this->createRedirectResponse($redirect);
            return $response;
        }

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

    /**
     * @param array $redirect
     * @return ResponseInterface
     * @throws SystemException
     */
    private function createRedirectResponse(array $redirect)
    {
        if (strpos($redirect['url'], 'http') === 0) { // A valid URL? Take it.
            $location = $redirect['url'];
        } else {
            $location = $this->urlGenerator->generate($redirect['url']); // A internal route? Generate URL.
        }
        $response = $this->httpFactory
            ->createResponse($redirect['status'])
            ->withHeader('Location', $location);
        return $response;
    }
}
