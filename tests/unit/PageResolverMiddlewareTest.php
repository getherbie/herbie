<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\middlewares\PageResolverMiddleware;
use herbie\Page;
use herbie\PageList;
use herbie\PageRepositoryInterface;
use herbie\UrlManager;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PageResolverMiddlewareTest extends Unit
{
    private ?ServerRequestInterface $captured = null;

    public function testPageRouteAndParamsAreSetAsRequestAttributes(): void
    {
        $page = new Page(['title' => 'About']);
        $middleware = $this->createMiddleware($page, 'about', []);

        $response = $middleware->process(new ServerRequest(), $this->capturingHandler());

        $this->assertSame($page, $this->captured?->getAttribute(
            PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_PAGE
        ));
        $this->assertSame('about', $this->captured?->getAttribute(
            PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_ROUTE
        ));
        $this->assertSame([], $this->captured?->getAttribute(
            PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_ROUTE_PARAMS
        ));
        $this->assertSame('handler-response', (string)$response->getBody());
    }

    public function testUnknownRouteSetsANullPage(): void
    {
        $middleware = $this->createMiddleware(null, 'missing', []);

        $middleware->process(new ServerRequest(), $this->capturingHandler());

        $this->assertNull($this->captured?->getAttribute(
            PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_PAGE
        ));
        $this->assertSame('missing', $this->captured?->getAttribute(
            PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_ROUTE
        ));
    }

    public function testTheOriginalRequestIsNotModified(): void
    {
        $middleware = $this->createMiddleware(new Page(), 'about', []);
        $request = new ServerRequest();

        $middleware->process($request, $this->capturingHandler());

        // attributes are set on a copy, as required by PSR-7
        $this->assertNull($request->getAttribute(PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_ROUTE));
        $this->assertSame('about', $this->captured?->getAttribute(
            PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_ROUTE
        ));
    }

    public function testExistingRequestAttributesAreKept(): void
    {
        $middleware = $this->createMiddleware(new Page(), 'about', []);
        $request = (new ServerRequest())->withAttribute('custom', 'value');

        $middleware->process($request, $this->capturingHandler());

        $this->assertSame('value', $this->captured?->getAttribute('custom'));
    }

    private function createMiddleware(?Page $page, string $route, array $routeParams): PageResolverMiddleware
    {
        $pageList = new PageList($page === null ? [] : [$route => $page]);
        $pageRepository = new class ($pageList) implements PageRepositoryInterface {
            public function __construct(private PageList $pageList)
            {
            }

            public function find(string $id): ?Page
            {
                return $this->pageList->getItem($id);
            }

            public function findAll(): PageList
            {
                return $this->pageList;
            }
        };

        return new PageResolverMiddleware($pageRepository, $this->createUrlManager($route));
    }

    /**
     * UrlManager reads the request in its constructor, so the route cannot be injected
     * directly. A catch all rule that maps the path to the wanted route does the job.
     */
    private function createUrlManager(string $route): UrlManager
    {
        return new UrlManager(
            (new ServerRequest())->withUri(new Uri('http://example.com/' . $route)),
            ['rules' => [['(.+)', $route]]]
        );
    }

    private function capturingHandler(): RequestHandlerInterface
    {
        return new class ($this) implements RequestHandlerInterface {
            public function __construct(private PageResolverMiddlewareTest $test)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->test->capture($request);
                return new TextResponse('handler-response');
            }
        };
    }

    public function capture(ServerRequestInterface $request): void
    {
        $this->captured = $request;
    }
}
