<?php

declare(strict_types=1);

namespace herbie;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PageResolverMiddleware implements MiddlewareInterface
{
    public const HERBIE_REQUEST_ATTRIBUTE_PAGE = 'HERBIE_PAGE';
    public const HERBIE_REQUEST_ATTRIBUTE_PATH = 'HERBIE_PATH';
    public const HERBIE_REQUEST_ATTRIBUTE_ROUTE = 'HERBIE_ROUTE';
    public const HERBIE_REQUEST_ATTRIBUTE_ROUTE_PARAMS = 'HERBIE_ROUTE_PARAMS';

    private PageRepositoryInterface $pageRepository;
    private Route $route;
    private UrlMatcher $urlMatcher;

    /**
     * PageResolverMiddleware constructor.
     */
    public function __construct(
        PageRepositoryInterface $pageRepository,
        Route $route,
        UrlMatcher $urlMatcher
    ) {
        $this->pageRepository = $pageRepository;
        $this->route = $route;
        $this->urlMatcher = $urlMatcher;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $pathInfo = $this->route->getPath();
        $route = $this->route->getRoute();

        $matchedRoute = $this->urlMatcher->match($route);

        $page = null;
        $routeParams = [];

        if ($matchedRoute) {
            $page = $this->pageRepository->find($matchedRoute['path']);
            if ($page) {
                $page->setRoute($matchedRoute['route']); // inject route
                $routeParams = $matchedRoute['params'];
            }
        }

        $request = $request
            ->withAttribute(self::HERBIE_REQUEST_ATTRIBUTE_PAGE, $page)
            ->withAttribute(self::HERBIE_REQUEST_ATTRIBUTE_PATH, $pathInfo)
            ->withAttribute(self::HERBIE_REQUEST_ATTRIBUTE_ROUTE, $route)
            ->withAttribute(self::HERBIE_REQUEST_ATTRIBUTE_ROUTE_PARAMS, $routeParams);

        return $handler->handle($request);
    }
}
