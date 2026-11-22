<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use DateInterval;
use herbie\EventManager;
use herbie\events\LayoutRenderedEvent;
use herbie\events\RenderLayoutEvent;
use herbie\events\RenderSegmentEvent;
use herbie\HttpException;
use herbie\middlewares\PageRendererMiddleware;
use herbie\middlewares\PageResolverMiddleware;
use herbie\Page;
use herbie\NullCache;
use Psr\SimpleCache\CacheInterface;
use herbie\UrlManager;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PageRendererMiddlewareTest extends Unit
{
    public function testMissingPageThrowsNotFound(): void
    {
        $middleware = $this->createMiddleware();

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);

        $middleware->process(new ServerRequest(), $this->terminalHandler());
    }

    public function testPageWithoutLayoutRendersItsSegments(): void
    {
        $page = new Page(['layout' => '', 'title' => 'Plain'], ['a' => 'first', 'b' => 'second']);
        $middleware = $this->createMiddleware($page);

        $response = $middleware->process($this->requestFor($page), $this->terminalHandler());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('firstsecond', (string)$response->getBody());
    }

    public function testPageWithLayoutDispatchesTheLayoutEvent(): void
    {
        $page = new Page(['layout' => 'default'], ['a' => 'first']);
        $eventManager = new EventManager();
        $dispatched = [];
        $eventManager->addListener(RenderLayoutEvent::class, function (RenderLayoutEvent $event) use (&$dispatched) {
            $dispatched[] = $event->getLayout();
            $event->setContent('rendered by the listener');
        });
        $middleware = $this->createMiddleware($page, $eventManager);

        $response = $middleware->process($this->requestFor($page), $this->terminalHandler());

        $this->assertSame(['default'], $dispatched);
        $this->assertSame('rendered by the listener', (string)$response->getBody());
    }

    public function testSegmentEventCanModifyTheSegment(): void
    {
        $page = new Page(['layout' => ''], ['a' => 'original']);
        $eventManager = new EventManager();
        $eventManager->addListener(RenderSegmentEvent::class, function (RenderSegmentEvent $event) {
            $event->setSegment(strtoupper($event->getSegment()));
        });
        $middleware = $this->createMiddleware($page, $eventManager);

        $response = $middleware->process($this->requestFor($page), $this->terminalHandler());

        $this->assertSame('ORIGINAL', (string)$response->getBody());
    }

    public function testLayoutRenderedEventCanModifyTheFinalContent(): void
    {
        $page = new Page(['layout' => ''], ['a' => 'content']);
        $eventManager = new EventManager();
        $eventManager->addListener(LayoutRenderedEvent::class, function (LayoutRenderedEvent $event) {
            $event->setContent($event->getContent() . ' + suffix');
        });
        $middleware = $this->createMiddleware($page, $eventManager);

        $response = $middleware->process($this->requestFor($page), $this->terminalHandler());

        $this->assertSame('content + suffix', (string)$response->getBody());
    }

    public function testContentTypeIsTakenFromThePage(): void
    {
        $page = new Page(['layout' => '', 'content_type' => 'text/xml'], ['a' => '<x/>']);
        $middleware = $this->createMiddleware($page);

        $response = $middleware->process($this->requestFor($page), $this->terminalHandler());

        $this->assertSame('text/xml', $response->getHeaderLine('Content-Type'));
    }

    public function testRedirectToAnInternalRouteIsResolvedToAUrl(): void
    {
        $page = new Page(['redirect' => ['about', 301]]);
        $middleware = $this->createMiddleware($page);

        $response = $middleware->process($this->requestFor($page), $this->terminalHandler());

        $this->assertSame(301, $response->getStatusCode());
        $this->assertStringContainsString('about', $response->getHeaderLine('Location'));
    }

    public function testRedirectToAnExternalUrlIsUsedAsItIs(): void
    {
        $page = new Page(['redirect' => ['https://example.com/target', 302]]);
        $middleware = $this->createMiddleware($page);

        $response = $middleware->process($this->requestFor($page), $this->terminalHandler());

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://example.com/target', $response->getHeaderLine('Location'));
    }

    public function testCachedContentIsUsedInsteadOfRendering(): void
    {
        $page = new Page(['layout' => ''], ['a' => 'fresh']);
        $cache = new class implements CacheInterface {
            #[\Override]
            public function get(string $key, mixed $default = null): mixed
            {
                return 'from the cache';
            }

            #[\Override]
            public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
            {
                return true;
            }

            #[\Override]
            public function delete(string $key): bool
            {
                return true;
            }

            #[\Override]
            public function clear(): bool
            {
                return true;
            }

            #[\Override]
            public function getMultiple(iterable $keys, mixed $default = null): iterable
            {
                return [];
            }

            #[\Override]
            public function setMultiple(iterable $values, int|DateInterval|null $ttl = null): bool
            {
                return true;
            }

            #[\Override]
            public function deleteMultiple(iterable $keys): bool
            {
                return true;
            }

            #[\Override]
            public function has(string $key): bool
            {
                return true;
            }
        };

        $middleware = $this->createMiddleware($page, new EventManager(), $cache);

        $response = $middleware->process($this->requestFor($page), $this->terminalHandler());

        $this->assertSame('from the cache', (string)$response->getBody());
    }

    private function createMiddleware(
        ?Page $page = null,
        ?EventManager $eventManager = null,
        ?CacheInterface $cache = null,
    ): PageRendererMiddleware {
        return new PageRendererMiddleware(
            $cache ?? new NullCache(),
            $eventManager ?? new EventManager(),
            new ResponseFactory(),
            $this->createUrlManager(),
            ['cache' => $cache !== null],
        );
    }

    private function createUrlManager(): UrlManager
    {
        return new UrlManager(new ServerRequest(), ['niceUrls' => false]);
    }

    private function requestFor(Page $page): ServerRequestInterface
    {
        return (new ServerRequest())
            ->withUri(new Uri('http://example.com/'))
            ->withAttribute(PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_PAGE, $page)
            ->withAttribute(PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_ROUTE, '');
    }

    private function terminalHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \LogicException('The terminal handler must not be called.');
            }
        };
    }
}
