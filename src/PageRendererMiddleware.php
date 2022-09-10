<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\SimpleCache\CacheInterface;
use Tebe\HttpFactory\HttpFactory;

final class PageRendererMiddleware implements MiddlewareInterface
{
    private CacheInterface $cache;

    private Environment $environment;

    private HttpFactory $httpFactory;

    private EventManager $eventManager;

    private FilterChainManager $filterChainManager;

    private Config $config;

    private UrlGenerator $urlGenerator;

    /**
     * PageRendererMiddleware constructor.
     */
    public function __construct(
        CacheInterface $cache,
        Config $config,
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
     * @throws HttpException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var Page $page */
        $page = $request->getAttribute(HERBIE_REQUEST_ATTRIBUTE_PAGE, null);

        if (is_null($page)) {
            throw HttpException::notFound($this->environment->getRoute());
        }

        /** @var array $routeParams */
        $routeParams = $request->getAttribute(HERBIE_REQUEST_ATTRIBUTE_ROUTE_PARAMS, []);

        return $this->renderPage($page, $routeParams);
    }

    /**
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
                $renderedSegment = (string)$this->filterChainManager->execute('renderSegment', $segment, $context);
                $segments[$segmentId] = $renderedSegment;
            }
            $segments = (array)$this->filterChainManager->execute('renderContent', $segments, $context);
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

    private function createRedirectResponse(array $redirect): ResponseInterface
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
