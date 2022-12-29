<?php

declare(strict_types=1);

namespace herbie\middlewares;

use herbie\PageRepositoryInterface;
use herbie\UrlManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PageResolverMiddleware implements MiddlewareInterface
{
    public const HERBIE_REQUEST_ATTRIBUTE_PAGE = 'HERBIE_PAGE';
    public const HERBIE_REQUEST_ATTRIBUTE_ROUTE = 'HERBIE_ROUTE';
    public const HERBIE_REQUEST_ATTRIBUTE_ROUTE_PARAMS = 'HERBIE_ROUTE_PARAMS';

    private PageRepositoryInterface $pageRepository;
    private UrlManager $urlManager;

    /**
     * PageResolverMiddleware constructor.
     */
    public function __construct(
        PageRepositoryInterface $pageRepository,
        UrlManager $urlManager
    ) {
        $this->pageRepository = $pageRepository;
        $this->urlManager = $urlManager;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        [$route, $routeParams] = $this->urlManager->parseRequest();

        $page = $this->pageRepository->findAll()->getItem($route);

        $request = $request
            ->withAttribute(self::HERBIE_REQUEST_ATTRIBUTE_PAGE, $page)
            ->withAttribute(self::HERBIE_REQUEST_ATTRIBUTE_ROUTE, $route)
            ->withAttribute(self::HERBIE_REQUEST_ATTRIBUTE_ROUTE_PARAMS, $routeParams);

        return $handler->handle($request);
    }
}
