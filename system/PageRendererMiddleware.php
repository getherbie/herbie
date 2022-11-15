<?php

declare(strict_types=1);

namespace herbie;

use herbie\event\ContentRenderedEvent;
use herbie\event\LayoutRenderedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
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
    private FilterChainManager $filterChainManager;
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
        FilterChainManager $filterChainManager,
        HttpFactory $httpFactory,
        UrlManager $urlManager,
        array $options = []
    ) {
        $this->cache = $cache;
        $this->httpFactory = $httpFactory;
        $this->eventManager = $eventManager;
        $this->filterChainManager = $filterChainManager;
        $this->urlManager = $urlManager;
        $this->cacheEnable = (bool)($options['cache'] ?? false);
        $this->cacheTTL = (int)($options['cacheTTL'] ?? 0);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Page|null $page */
        $page = $request->getAttribute(PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_PAGE);

        if (is_null($page)) {
            throw HttpException::notFound(PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_ROUTE);
        }

        /** @var array $routeParams */
        $routeParams = $request->getAttribute(PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_ROUTE_PARAMS, []);

        return $this->renderPage($page, $routeParams);
    }

    private function renderPage(Page $page, array $routeParams): ResponseInterface
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
            $context = [
                'page' => $page,
                'routeParams' => $routeParams
            ];

            // render segments
            $segments = [];
            foreach ($page->getSegments() as $segmentId => $segment) {
                $renderedSegment = (string)$this->filterChainManager->execute('renderSegment', $segment, $context);
                $segments[$segmentId] = $renderedSegment;
            }
            $segments = $this->eventManager->dispatch(new ContentRenderedEvent($segments))->getSegments();

            // render layout
            if (empty($page->getLayout())) {
                $content = implode('', $segments);
            } else {
                $content = (string)$this->filterChainManager->execute('renderLayout', (string)$content, array_merge([
                    'content' => $segments
                ], $context));
            }

            $content = $this->eventManager->dispatch(new LayoutRenderedEvent($content))->getContent();

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
