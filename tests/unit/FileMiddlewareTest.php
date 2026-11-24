<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Alias;
use herbie\HttpException;
use herbie\middlewares\FileMiddleware;
use herbie\middlewares\PageResolverMiddleware;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class FileMiddlewareTest extends Unit
{
    private string $mediaDir;
    private string $outsideDir;

    protected function _before(): void
    {
        $this->mediaDir = sys_get_temp_dir() . '/herbie-file-test-' . bin2hex(random_bytes(4));
        $this->outsideDir = sys_get_temp_dir() . '/herbie-outside-test-' . bin2hex(random_bytes(4));
        mkdir($this->mediaDir . '/sub', 0777, true);
        mkdir($this->outsideDir, 0777, true);
        file_put_contents($this->outsideDir . '/secret.txt', 'secret');
    }

    protected function _after(): void
    {
        $this->removeDirectory($this->mediaDir);
        $this->removeDirectory($this->outsideDir);
    }

    public function testRequestWithoutAFileRouteIsPassedToTheNextMiddleware(): void
    {
        $response = $this->createMiddleware()->process($this->requestFor('some/page'), $this->terminalHandler());

        $this->assertSame('next middleware', (string)$response->getBody());
    }

    public function testMediaRouteServesAFileInlineFromTheMediaAlias(): void
    {
        file_put_contents($this->mediaDir . '/sub/photo.JPG', 'image content');

        $response = $this->createMiddleware()->process(
            $this->requestFor('media/sub/photo.JPG'),
            $this->terminalHandler(),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/jpeg', $response->getHeaderLine('Content-Type'));
        $this->assertSame('13', $response->getHeaderLine('Content-Length'));
        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        $this->assertSame('', $response->getHeaderLine('Content-Disposition'));
        $this->assertSame('image content', (string)$response->getBody());
    }

    public function testDownloadRouteServesAFileAsAttachment(): void
    {
        file_put_contents($this->mediaDir . '/über.pdf', '%PDF-1.4 test');

        $response = $this->createMiddleware()->process(
            $this->requestFor('download/' . rawurlencode('über.pdf')),
            $this->terminalHandler(),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('attachment;', $response->getHeaderLine('Content-Disposition'));
        $this->assertStringContainsString("filename*=UTF-8''", $response->getHeaderLine('Content-Disposition'));
        $this->assertSame('%PDF-1.4 test', (string)$response->getBody());
    }

    public function testDownloadRouteAcceptsAnyFileType(): void
    {
        file_put_contents($this->mediaDir . '/archive.unknownext', 'content');

        $response = $this->createMiddleware()->process(
            $this->requestFor('download/archive.unknownext'),
            $this->terminalHandler(),
        );

        $this->assertSame('application/octet-stream', $response->getHeaderLine('Content-Type'));
        $this->assertSame('content', (string)$response->getBody());
    }

    public function testMediaRouteRejectsUnknownFileTypes(): void
    {
        file_put_contents($this->mediaDir . '/archive.rar', 'content');

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);

        $this->createMiddleware()->process($this->requestFor('media/archive.rar'), $this->terminalHandler());
    }

    public function testMissingFileThrowsNotFound(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);

        $this->createMiddleware()->process($this->requestFor('media/missing.png'), $this->terminalHandler());
    }

    public function testPathTraversalOutsideTheMediaDirectoryIsRejected(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);

        $this->createMiddleware()->process(
            $this->requestFor('media/../' . basename($this->outsideDir) . '/secret.txt'),
            $this->terminalHandler(),
        );
    }

    public function testSymlinkOutsideTheMediaDirectoryIsRejected(): void
    {
        symlink($this->outsideDir . '/secret.txt', $this->mediaDir . '/secret.txt');

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);

        $this->createMiddleware()->process($this->requestFor('media/secret.txt'), $this->terminalHandler());
    }

    public function testRoutesAreDerivedFromTheConfiguredUrls(): void
    {
        file_put_contents($this->mediaDir . '/photo.png', 'image content');
        $middleware = $this->createMiddleware('/base/assets', '/base/get', '/base/');

        $mediaResponse = $middleware->process($this->requestFor('assets/photo.png'), $this->terminalHandler());
        $downloadResponse = $middleware->process($this->requestFor('get/photo.png'), $this->terminalHandler());

        $this->assertSame('image/png', $mediaResponse->getHeaderLine('Content-Type'));
        $this->assertSame('', $mediaResponse->getHeaderLine('Content-Disposition'));
        $this->assertStringContainsString('attachment;', $downloadResponse->getHeaderLine('Content-Disposition'));
    }

    private function createMiddleware(
        string $mediaUrl = '/media',
        string $downloadUrl = '/download',
        string $webUrl = '/',
    ): FileMiddleware {
        return new FileMiddleware(
            new Alias(['@media' => $this->mediaDir]),
            new StreamFactory(),
            new ResponseFactory(),
            $mediaUrl,
            $downloadUrl,
            $webUrl,
        );
    }

    private function requestFor(string $route): ServerRequestInterface
    {
        return (new ServerRequest())->withAttribute(
            PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_ROUTE,
            $route,
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

    private function removeDirectory(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($dir);
    }
}
