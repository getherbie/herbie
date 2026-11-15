<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\MiddlewareDispatcher;
use LogicException;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewareDispatcherTest extends Unit
{
    public function testMiddlewareIsCalled(): void
    {
        $calls = [];
        $middleware = $this->createMiddleware('first', $calls);

        $dispatcher = new MiddlewareDispatcher([$middleware], [], [], [$this->createTerminal()], '');
        $response = $dispatcher->handle(new ServerRequest());

        $this->assertSame(['first'], $calls);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testMiddlewaresAreCalledInOrder(): void
    {
        $calls = [];
        $dispatcher = new MiddlewareDispatcher(
            [$this->createMiddleware('prepend', $calls)],
            [$this->createMiddleware('app', $calls)],
            [],
            [$this->createMiddleware('append', $calls), $this->createTerminal()],
            ''
        );

        $dispatcher->handle(new ServerRequest());

        $this->assertSame(['prepend', 'app', 'append'], $calls);
    }

    public function testRouteMiddlewareIsAddedForMatchingRoute(): void
    {
        $calls = [];
        $dispatcher = new MiddlewareDispatcher(
            [],
            [$this->createMiddleware('app', $calls)],
            [['^admin', $this->createMiddleware('route-admin', $calls)]],
            [$this->createTerminal()],
            'admin/settings'
        );

        $dispatcher->handle(new ServerRequest());

        $this->assertSame(['app', 'route-admin'], $calls);
    }

    public function testRouteMiddlewareIsSkippedForOtherRoutes(): void
    {
        $calls = [];
        $dispatcher = new MiddlewareDispatcher(
            [],
            [$this->createMiddleware('app', $calls)],
            [['^admin', $this->createMiddleware('route-admin', $calls)]],
            [$this->createTerminal()],
            'blog'
        );

        $dispatcher->handle(new ServerRequest());

        $this->assertSame(['app'], $calls);
    }

    public function testGetMiddlewaresReturnsAllMiddlewares(): void
    {
        $calls = [];
        $a = $this->createMiddleware('a', $calls);
        $b = $this->createMiddleware('b', $calls);

        $dispatcher = new MiddlewareDispatcher([$a], [$b], [], [], '');

        $this->assertCount(2, $dispatcher->getMiddlewares());
    }

    public function testCallableIsWrappedInAMiddleware(): void
    {
        $calls = [];
        $callable = static function (ServerRequestInterface $request, RequestHandlerInterface $handler) use (&$calls): ResponseInterface {
            $calls[] = 'callable';
            return $handler->handle($request);
        };
        $dispatcher = new MiddlewareDispatcher(
            [$callable, $this->createMiddleware('last', $calls), $this->createTerminal()],
            [],
            [],
            [],
            ''
        );

        $dispatcher->handle(new ServerRequest());

        $this->assertSame(['callable', 'last'], $calls);
    }

    public function testMissingTerminalThrowsLogicException(): void
    {
        $calls = [];
        $dispatcher = new MiddlewareDispatcher([$this->createMiddleware('first', $calls)], [], [], [], '');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The middleware chain is exhausted.');

        $dispatcher->handle(new ServerRequest());
    }

    public function testMissingTerminalThrowsBeforeAnyMiddlewareRuns(): void
    {
        $calls = [];
        $dispatcher = new MiddlewareDispatcher([$this->createMiddleware('first', $calls)], [], [], [], '');

        try {
            $dispatcher->handle(new ServerRequest());
        } catch (LogicException $e) {
            // the first middleware has run, because it calls the handler itself
            $this->assertSame(['first'], $calls);
        }
    }

    public function testEmptyChainThrowsLogicException(): void
    {
        $dispatcher = new MiddlewareDispatcher([], [], [], [], '');

        $this->expectException(LogicException::class);
        $dispatcher->handle(new ServerRequest());
    }

    /**
     * The chain needs a last middleware that creates the response.
     */
    private function createTerminal(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return new Response();
            }
        };
    }

    /**
     * @param array<int, string> $calls
     */
    private function createMiddleware(string $name, array &$calls): MiddlewareInterface
    {
        return new class ($name, $calls) implements MiddlewareInterface {
            /** @param array<int, string> $calls */
            public function __construct(private string $name, private array &$calls)
            {
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $this->calls[] = $this->name;
                return $handler->handle($request);
            }
        };
    }
}
