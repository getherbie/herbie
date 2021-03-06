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

class PageResolverMiddleware implements MiddlewareInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var UrlMatcher
     */
    private $urlMatcher;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * PageResolverMiddleware constructor.
     * @param Environment $environment
     * @param PageRepositoryInterface $pageRepository
     * @param UrlMatcher $urlMatcher
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

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $this->environment->getRoute();
        $matchedRoute = $this->urlMatcher->match($route);

        if (empty($matchedRoute)) {
            $page = null;
            $routeParams = [];
        } else {
            $page = $this->pageRepository->find($matchedRoute['path']);
            $page->setRoute($matchedRoute['route']); // inject route
            $routeParams = $matchedRoute['params'];
        }

        $request = $request
            ->withAttribute(HERBIE_REQUEST_ATTRIBUTE_PAGE, $page)
            ->withAttribute(HERBIE_REQUEST_ATTRIBUTE_ROUTE, $route)
            ->withAttribute(HERBIE_REQUEST_ATTRIBUTE_ROUTE_PARAMS, $routeParams);
        return $handler->handle($request);
    }
}
