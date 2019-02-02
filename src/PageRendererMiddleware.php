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

namespace Herbie;

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
     * @var FilterChainManager
     */
    private $filterChainManager;

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * PageRendererMiddleware constructor.
     * @param CacheInterface $cache
     * @param Configuration $config
     * @param Environment $environment
     * @param EventManager $eventManager
     * @param FilterChainManager $filterChainManager
     * @param HttpFactory $httpFactory
     * @param UrlGenerator $urlGenerator
     */
    public function __construct(
        CacheInterface $cache,
        Configuration $config,
        Environment $environment,
        EventManager $eventManager,
        FilterChainManager $filterChainManager,
        HttpFactory $httpFactory,
        UrlGenerator $urlGenerator
    ) {
        $this->cache = $cache;
        $this->environment = $environment;
        $this->httpFactory = $httpFactory;
        $this->eventManager = $eventManager;
        $this->filterChainManager = $filterChainManager;
        $this->config = $config;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \Exception
     * @throws \Psr\SimpleCache\\InvalidArgumentException
     * @throws \Throwable
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Page $page */
        $page = $request->getAttribute(HERBIE_REQUEST_ATTRIBUTE_PAGE, null);

        if (is_null($page)) {
            $message = sprintf(
                'Server request attribute "%s" not set',
                HERBIE_REQUEST_ATTRIBUTE_PAGE
            );
            throw new \InvalidArgumentException($message);
        }

        /** @var array $routeParams */
        $routeParams = $request->getAttribute(HERBIE_REQUEST_ATTRIBUTE_ROUTE_PARAMS, []);

        return $this->renderPage($page, $routeParams);
    }

    /**
     * @param Page $page
     * @param array $routeParams
     * @return ResponseInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Throwable
     */
    private function renderPage(Page $page, array $routeParams): ResponseInterface
    {
        $redirect = $page->getRedirect();

        if (!empty($redirect)) {
            $response = $this->createRedirectResponse($redirect);
            return $response;
        }

        $rendered = null;

        $cacheId = 'page-' . $this->environment->getRoute();
        if (!empty($page->getCached())) {
            $rendered = $this->cache->get($cacheId);
        }

        if (null === $rendered) {
            $context = [
                'page' => $page,
                'routeParams' => $routeParams
            ];

            // render segments
            $segments = [];
            foreach ($page->getSegments() as $segmentId => $segment) {
                $renderedSegment = (string)$this->filterChainManager->execute('renderContent', $segment, $context);
                $segments[$segmentId] = $renderedSegment;
            }
            $this->eventManager->trigger('onContentRendered', $segments, $page->toArray());

            // render layout
            $content = '';
            if (empty($page->getLayout())) {
                $content = implode('', $segments);
            } else {
                $content = (string)$this->filterChainManager->execute('renderLayout', $content, array_merge([
                    'content' => $segments
                ], $context));
            }
            $this->eventManager->trigger('onLayoutRendered', $content, ['page' => $page]);

            if (!empty($page->getCached())) {
                $this->cache->set($cacheId, $content);
            }
            $rendered = $content;
        }

        $response = $this->httpFactory->createResponse(200);

        $response->getBody()->write($rendered);
        $response->withHeader('Content-Type', $page->getContentType());

        return $response;
    }

    /**
     * @param array $redirect
     * @return ResponseInterface
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
