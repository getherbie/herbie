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
    public const HERBIE_REQUEST_ATTRIBUTE_ROUTE = 'HERBIE_ROUTE';
    public const HERBIE_REQUEST_ATTRIBUTE_ROUTE_PARAMS = 'HERBIE_ROUTE_PARAMS';

    private Environment $environment;

    private UrlMatcher $urlMatcher;

    private PageRepositoryInterface $pageRepository;

    /**
     * PageResolverMiddleware constructor.
     */
    public function __construct(
        Environment $environment,
        PageRepositoryInterface $pageRepository,
        UrlMatcher $urlMatcher
    ) {
        $this->environment = $environment;
        $this->urlMatcher = $urlMatcher;
        $this->pageRepository = $pageRepository;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $this->environment->getRoute();
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
            ->withAttribute(self::HERBIE_REQUEST_ATTRIBUTE_ROUTE, $route)
            ->withAttribute(self::HERBIE_REQUEST_ATTRIBUTE_ROUTE_PARAMS, $routeParams);

        return $handler->handle($request);
    }
}
