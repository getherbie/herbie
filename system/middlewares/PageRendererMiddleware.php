<?php

declare(strict_types=1);

namespace herbie\middlewares;

use herbie\EventManager;
use herbie\events\ContentRenderedEvent;
use herbie\events\LayoutRenderedEvent;
use herbie\events\RenderLayoutEvent;
use herbie\events\RenderPageEvent;
use herbie\events\RenderSegmentEvent;
use herbie\HttpException;
use herbie\Page;
use herbie\UrlManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Tebe\HttpFactory\HttpFactory;

final class PageRendererMiddleware implements MiddlewareInterface
{
    private CacheInterface $cache;
    private EventManager $eventManager;
    private HttpFactory $httpFactory;
    private UrlManager $urlManager;
    private bool $cacheEnable;
    private int $cacheTTL;

    /**
     * PageRendererMiddleware constructor.
     */
    public function __construct(
        CacheInterface $cache,
        EventManager $eventManager,
        HttpFactory $httpFactory,
        UrlManager $urlManager,
        array $options = []
    ) {
        $this->cache = $cache;
        $this->httpFactory = $httpFactory;
        $this->eventManager = $eventManager;
        $this->urlManager = $urlManager;
        $this->cacheEnable = (bool)($options['cache'] ?? false);
        $this->cacheTTL = (int)($options['cacheTTL'] ?? 0);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var string $route */
        $route = $request->getAttribute(PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_ROUTE, '');

        /** @var array $routeParams */
        $routeParams = $request->getAttribute(PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_ROUTE_PARAMS, []);

        /** @var Page|null $page */
        $page = $request->getAttribute(PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_PAGE);

        if ($page === null) {
            throw HttpException::notFound($route);
        }

        return $this->renderPage($page, $route, $routeParams);
    }

    private function renderPage(Page $page, string $route, array $routeParams): ResponseInterface
    {
        $redirect = $page->getRedirect();

        if (!empty($redirect)) {
            return $this->createRedirectResponse(...$redirect);
        }

        $content = null;

        $cacheId = $page->getCacheId();
        if ($this->cacheEnable && !empty($page->getCached())) {
            $content = $this->cache->get($cacheId);
        }

        if (null === $content) {
            $this->eventManager->dispatch(new RenderPageEvent($page, $route, $routeParams));

            // render segments
            $segments = [];
            foreach ($page->getSegments() as $segmentId => $segment) {
                /** @var RenderSegmentEvent $event */
                $event = $this->eventManager->dispatch(
                    new RenderSegmentEvent($page, $segment, $segmentId, $this->urlManager)
                );
                $renderedSegment = $event->getSegment();
                $segments[$segmentId] = $renderedSegment;
            }

            /** @var ContentRenderedEvent $event */
            $event = $this->eventManager->dispatch(new ContentRenderedEvent($segments));
            $segments = $event->getSegments();

            // render layout
            if (empty($page->getLayout())) {
                $content = implode('', $segments);
            } else {
                /** @var RenderLayoutEvent $event */
                $event = $this->eventManager->dispatch(new RenderLayoutEvent($segments, $page->getLayout()));
                $content = $event->getContent();
            }

            /** @var LayoutRenderedEvent $event */
            $event = $this->eventManager->dispatch(new LayoutRenderedEvent($content));
            $content = $event->getContent();

            if ($this->cacheEnable && !empty($page->getCached())) {
                $this->cache->set($cacheId, $content, $this->cacheTTL);
            }
        }

        $response = $this->httpFactory->createResponse();
        $response->getBody()->write($content);
        return $response->withHeader('Content-Type', $page->getContentType());
    }

    private function createRedirectResponse(string $url, int $status): ResponseInterface
    {
        if (strpos($url, 'http') === 0) { // A valid URL? Take it.
            $location = $url;
        } else {
            $location = $this->urlManager->createUrl($url); // An internal route? Generate URL.
        }
        return $this->httpFactory
            ->createResponse($status)
            ->withHeader('Location', $location);
    }
}
