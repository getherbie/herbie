<?php

declare(strict_types=1);

namespace herbie\middlewares;

use herbie\PageRepositoryInterface;
use herbie\UrlManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class PageResolverMiddleware implements MiddlewareInterface
{
    public const string HERBIE_REQUEST_ATTRIBUTE_PAGE = 'HERBIE_PAGE';
    public const string HERBIE_REQUEST_ATTRIBUTE_ROUTE = 'HERBIE_ROUTE';
    public const string HERBIE_REQUEST_ATTRIBUTE_ROUTE_PARAMS = 'HERBIE_ROUTE_PARAMS';

    /**
     * PageResolverMiddleware constructor.
     */
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private UrlManager $urlManager,
    ) {}

    #[\Override]
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
