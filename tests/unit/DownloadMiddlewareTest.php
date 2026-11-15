<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Alias;
use herbie\HttpException;
use herbie\middlewares\DownloadMiddleware;
use herbie\middlewares\PageResolverMiddleware;
use herbie\SystemException;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DownloadMiddlewareTest extends Unit
{
    private string $storage;

    protected function _before(): void
    {
        $this->storage = sys_get_temp_dir() . '/herbie-download-test-' . bin2hex(random_bytes(4));
        mkdir($this->storage, 0777, true);
    }

    protected function _after(): void
    {
        foreach (glob($this->storage . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->storage);
    }

    public function testRequestWithoutTheDownloadRouteIsPassedToTheNextMiddleware(): void
    {
        $middleware = $this->createMiddleware();

        $response = $middleware->process($this->requestFor('some/page'), $this->terminalHandler());

        $this->assertSame('next middleware', (string)$response->getBody());
    }

    public function testPdfFileIsReturnedWithItsContentType(): void
    {
        file_put_contents($this->storage . '/manual.pdf', '%PDF-1.4 test');
        $middleware = $this->createMiddleware();

        $response = $middleware->process($this->requestFor('download/manual.pdf'), $this->terminalHandler());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->getHeaderLine('Content-type'));
        $this->assertSame('%PDF-1.4 test', (string)$response->getBody());
    }

    public function testMissingFileThrowsNotFound(): void
    {
        $middleware = $this->createMiddleware();

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);

        $middleware->process($this->requestFor('download/missing.pdf'), $this->terminalHandler());
    }

    public function testUnsupportedFileExtensionThrowsServerError(): void
    {
        file_put_contents($this->storage . '/notes.txt', 'text');
        $middleware = $this->createMiddleware();

        $this->expectException(SystemException::class);

        $middleware->process($this->requestFor('download/notes.txt'), $this->terminalHandler());
    }

    public function testFileNameWithUmlautsIsServed(): void
    {
        file_put_contents($this->storage . '/über.pdf', 'pdf content');
        $middleware = $this->createMiddleware();

        $response = $middleware->process($this->requestFor('download/über.pdf'), $this->terminalHandler());

        $this->assertSame('pdf content', (string)$response->getBody());
    }

    private function createMiddleware(): DownloadMiddleware
    {
        return new DownloadMiddleware(
            new Alias(['@storage' => $this->storage]),
            new StreamFactory(),
            new ResponseFactory(),
            ['route' => 'download', 'storagePath' => '@storage']
        );
    }

    private function requestFor(string $route): ServerRequestInterface
    {
        return (new ServerRequest())->withAttribute(
            PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_ROUTE,
            $route
        );
    }

    private function terminalHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new TextResponse('next middleware');
            }
        };
    }
}
