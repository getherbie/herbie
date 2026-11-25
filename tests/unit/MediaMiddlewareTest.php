<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Alias;
use herbie\HttpException;
use herbie\middlewares\MediaMiddleware;
use herbie\middlewares\PageResolverMiddleware;
use herbie\UrlManager;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MediaMiddlewareTest extends Unit
{
    private string $mediaDir;
    private string $outsideDir;

    protected function _before(): void
    {
        $this->mediaDir = sys_get_temp_dir() . '/herbie-media-test-' . bin2hex(random_bytes(4));
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

    public function testRequestWithoutAMediaRouteIsPassedToTheNextMiddleware(): void
    {
        $response = $this->createMiddleware()->process($this->requestFor('some/page'), $this->terminalHandler());

        $this->assertSame('next middleware', (string)$response->getBody());
    }

    public function testDisplayableFileIsServedInline(): void
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

    public function testDownloadParameterForcesAnAttachment(): void
    {
        file_put_contents($this->mediaDir . '/über.pdf', '%PDF-1.4 test');

        $response = $this->createMiddleware()->process(
            $this->requestFor('media/' . rawurlencode('über.pdf'), ['download' => '']),
            $this->terminalHandler(),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('attachment;', $response->getHeaderLine('Content-Disposition'));
        $this->assertStringContainsString("filename*=UTF-8''", $response->getHeaderLine('Content-Disposition'));
        $this->assertSame('%PDF-1.4 test', (string)$response->getBody());
    }

    public function testNonDisplayableFileIsServedAsAttachmentAutomatically(): void
    {
        file_put_contents($this->mediaDir . '/prices.xlsx', 'content');

        $response = $this->createMiddleware()->process($this->requestFor('media/prices.xlsx'), $this->terminalHandler());

        $this->assertStringContainsString('attachment;', $response->getHeaderLine('Content-Disposition'));
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->getHeaderLine('Content-Type'),
        );
    }

    public function testUnknownFileTypeIsServedAsOctetStreamAttachment(): void
    {
        file_put_contents($this->mediaDir . '/archive.unknownext', 'content');

        $response = $this->createMiddleware()->process(
            $this->requestFor('media/archive.unknownext'),
            $this->terminalHandler(),
        );

        $this->assertSame('application/octet-stream', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('attachment;', $response->getHeaderLine('Content-Disposition'));
    }

    public function testSvgIsNeverServedInline(): void
    {
        file_put_contents($this->mediaDir . '/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');

        $response = $this->createMiddleware()->process($this->requestFor('media/logo.svg'), $this->terminalHandler());

        $this->assertSame('image/svg+xml', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('attachment;', $response->getHeaderLine('Content-Disposition'));
    }

    public function testTextTypesGetACharset(): void
    {
        file_put_contents($this->mediaDir . '/readme.txt', 'text');

        $response = $this->createMiddleware()->process($this->requestFor('media/readme.txt'), $this->terminalHandler());

        $this->assertSame('text/plain; charset=utf-8', $response->getHeaderLine('Content-Type'));
    }

    public function testHiddenFilesAreNotServed(): void
    {
        file_put_contents($this->mediaDir . '/.env', 'SECRET=1');

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);

        $this->createMiddleware()->process($this->requestFor('media/.env'), $this->terminalHandler());
    }

    public function testFilesInHiddenDirectoriesAreNotServed(): void
    {
        mkdir($this->mediaDir . '/.git');
        file_put_contents($this->mediaDir . '/.git/config', 'config');

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);

        $this->createMiddleware()->process($this->requestFor('media/.git/config'), $this->terminalHandler());
    }

    public function testPhpFilesAreNotServed(): void
    {
        file_put_contents($this->mediaDir . '/script.php', '<?php echo 1;');

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);

        $this->createMiddleware()->process($this->requestFor('media/script.php'), $this->terminalHandler());
    }

    public function testNullByteInThePathIsNotFound(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);

        $this->createMiddleware()->process($this->requestFor('media/photo.png%00.txt'), $this->terminalHandler());
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

    public function testRouteIsDerivedFromTheConfiguredUrl(): void
    {
        file_put_contents($this->mediaDir . '/photo.png', 'image content');
        $middleware = $this->createMiddleware('/base/assets', '/base/');

        $response = $middleware->process($this->requestFor('assets/photo.png'), $this->terminalHandler());
        $passedOn = $middleware->process($this->requestFor('media/photo.png'), $this->terminalHandler());

        $this->assertSame('image/png', $response->getHeaderLine('Content-Type'));
        $this->assertSame('next middleware', (string)$passedOn->getBody());
    }

    public function testResponsesAdvertiseByteRanges(): void
    {
        file_put_contents($this->mediaDir . '/clip.mp4', 'abcdefghijklm');

        $response = $this->createMiddleware()->process($this->requestFor('media/clip.mp4'), $this->terminalHandler());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('bytes', $response->getHeaderLine('Accept-Ranges'));
        $this->assertSame('', $response->getHeaderLine('Content-Range'));
        $this->assertSame('abcdefghijklm', (string)$response->getBody());
    }

    /**
     * @return array<string, array{string, string, string}> Range header, Content-Range, expected body
     */
    public static function satisfiableRanges(): array
    {
        return [
            'first bytes' => ['bytes=0-4', 'bytes 0-4/13', 'abcde'],
            'open end' => ['bytes=5-', 'bytes 5-12/13', 'fghijklm'],
            'last bytes' => ['bytes=-3', 'bytes 10-12/13', 'klm'],
            'suffix longer than the file' => ['bytes=-100', 'bytes 0-12/13', 'abcdefghijklm'],
            'end beyond the file' => ['bytes=10-100', 'bytes 10-12/13', 'klm'],
            'single byte' => ['bytes=7-7', 'bytes 7-7/13', 'h'],
        ];
    }

    /**
     * @dataProvider satisfiableRanges
     */
    public function testRangeRequestIsAnsweredWithPartialContent(
        string $range,
        string $contentRange,
        string $expectedBody,
    ): void {
        file_put_contents($this->mediaDir . '/clip.mp4', 'abcdefghijklm');

        $response = $this->createMiddleware()->process(
            $this->requestFor('media/clip.mp4')->withHeader('Range', $range),
            $this->terminalHandler(),
        );

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame($contentRange, $response->getHeaderLine('Content-Range'));
        $this->assertSame((string)strlen($expectedBody), $response->getHeaderLine('Content-Length'));
        $this->assertSame('video/mp4', $response->getHeaderLine('Content-Type'));
        $this->assertSame('bytes', $response->getHeaderLine('Accept-Ranges'));
        $this->assertSame($expectedBody, (string)$response->getBody());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsatisfiableRanges(): array
    {
        return [
            'start behind the end' => ['bytes=20-30'],
            'start at the size' => ['bytes=13-'],
            'empty suffix' => ['bytes=-0'],
        ];
    }

    /**
     * @dataProvider unsatisfiableRanges
     */
    public function testUnsatisfiableRangeIsAnsweredWith416(string $range): void
    {
        file_put_contents($this->mediaDir . '/clip.mp4', 'abcdefghijklm');

        $response = $this->createMiddleware()->process(
            $this->requestFor('media/clip.mp4')->withHeader('Range', $range),
            $this->terminalHandler(),
        );

        $this->assertSame(416, $response->getStatusCode());
        $this->assertSame('bytes */13', $response->getHeaderLine('Content-Range'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function ignoredRanges(): array
    {
        return [
            'multiple ranges' => ['bytes=0-1,3-4'],
            'last before first' => ['bytes=5-2'],
            'unknown unit' => ['items=0-1'],
            'no numbers' => ['bytes=-'],
            'garbage' => ['bytes=abc'],
        ];
    }

    /**
     * @dataProvider ignoredRanges
     */
    public function testUnsupportedRangeSendsTheWholeFile(string $range): void
    {
        file_put_contents($this->mediaDir . '/clip.mp4', 'abcdefghijklm');

        $response = $this->createMiddleware()->process(
            $this->requestFor('media/clip.mp4')->withHeader('Range', $range),
            $this->terminalHandler(),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', $response->getHeaderLine('Content-Range'));
        $this->assertSame('abcdefghijklm', (string)$response->getBody());
    }

    public function testRangeIsIgnoredForConditionalRequests(): void
    {
        file_put_contents($this->mediaDir . '/clip.mp4', 'abcdefghijklm');

        $response = $this->createMiddleware()->process(
            $this->requestFor('media/clip.mp4')
                ->withHeader('Range', 'bytes=0-4')
                ->withHeader('If-Range', '"etag"'),
            $this->terminalHandler(),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abcdefghijklm', (string)$response->getBody());
    }

    public function testResponsesCanBeCached(): void
    {
        [$etag, $lastModified] = $this->createClip();

        $response = $this->createMiddleware()->process($this->requestFor('media/clip.mp4'), $this->terminalHandler());

        $this->assertSame($etag, $response->getHeaderLine('ETag'));
        $this->assertSame($lastModified, $response->getHeaderLine('Last-Modified'));
        $this->assertSame('public, max-age=86400', $response->getHeaderLine('Cache-Control'));
    }

    /**
     * @return array<string, array{string, string}> Header name, value (placeholders {etag} and {date})
     */
    public static function matchingValidators(): array
    {
        return [
            'matching etag' => ['If-None-Match', '{etag}'],
            'weak etag' => ['If-None-Match', 'W/{etag}'],
            'etag in a list' => ['If-None-Match', '"other", {etag}'],
            'any etag' => ['If-None-Match', '*'],
            'unmodified since' => ['If-Modified-Since', '{date}'],
            'unmodified since a later date' => ['If-Modified-Since', 'Sun, 31 Dec 2023 23:59:59 GMT'],
        ];
    }

    /**
     * @dataProvider matchingValidators
     */
    public function testConditionalRequestIsAnsweredWith304(string $header, string $value): void
    {
        [$etag, $lastModified] = $this->createClip();

        $response = $this->createMiddleware()->process(
            $this->requestFor('media/clip.mp4')->withHeader($header, strtr($value, [
                '{etag}' => $etag,
                '{date}' => $lastModified,
            ])),
            $this->terminalHandler(),
        );

        $this->assertSame(304, $response->getStatusCode());
        $this->assertSame($etag, $response->getHeaderLine('ETag'));
        $this->assertSame($lastModified, $response->getHeaderLine('Last-Modified'));
        $this->assertSame('public, max-age=86400', $response->getHeaderLine('Cache-Control'));
        $this->assertSame('', (string)$response->getBody());
    }

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function nonMatchingValidators(): array
    {
        return [
            'other etag' => [['If-None-Match' => '"other"']],
            'modified since' => [['If-Modified-Since' => 'Tue, 14 Nov 2023 22:13:19 GMT']],
            'invalid date' => [['If-Modified-Since' => 'not a date']],
            'etag takes precedence over the date' => [[
                'If-None-Match' => '"other"',
                'If-Modified-Since' => 'Sun, 31 Dec 2023 23:59:59 GMT',
            ]],
        ];
    }

    /**
     * @dataProvider nonMatchingValidators
     * @param array<string, string> $headers
     */
    public function testNonMatchingConditionalRequestSendsTheFile(array $headers): void
    {
        $this->createClip();
        $request = $this->requestFor('media/clip.mp4');
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        $response = $this->createMiddleware()->process($request, $this->terminalHandler());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abcdefghijklm', (string)$response->getBody());
    }

    public function testConditionalRequestIsOnlyAnsweredWith304ForGetAndHead(): void
    {
        [$etag] = $this->createClip();

        $response = $this->createMiddleware()->process(
            $this->requestFor('media/clip.mp4')->withMethod('POST')->withHeader('If-None-Match', $etag),
            $this->terminalHandler(),
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testIfRangeWithTheEtagKeepsTheRange(): void
    {
        [$etag] = $this->createClip();

        $response = $this->createMiddleware()->process(
            $this->requestFor('media/clip.mp4')->withHeader('Range', 'bytes=0-4')->withHeader('If-Range', $etag),
            $this->terminalHandler(),
        );

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('abcde', (string)$response->getBody());
    }

    public function testIfRangeWithTheDateKeepsTheRange(): void
    {
        [, $lastModified] = $this->createClip();

        $response = $this->createMiddleware()->process(
            $this->requestFor('media/clip.mp4')
                ->withHeader('Range', 'bytes=0-4')
                ->withHeader('If-Range', $lastModified),
            $this->terminalHandler(),
        );

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('abcde', (string)$response->getBody());
    }

    public function testRangeOfAnEmptyFileSendsTheWholeFile(): void
    {
        file_put_contents($this->mediaDir . '/empty.mp3', '');

        $response = $this->createMiddleware()->process(
            $this->requestFor('media/empty.mp3')->withHeader('Range', 'bytes=0-4'),
            $this->terminalHandler(),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('0', $response->getHeaderLine('Content-Length'));
    }

    public function testRangeRequestKeepsTheAttachmentHeader(): void
    {
        file_put_contents($this->mediaDir . '/archive.zip', 'abcdefghijklm');

        $response = $this->createMiddleware()->process(
            $this->requestFor('media/archive.zip')->withHeader('Range', 'bytes=5-'),
            $this->terminalHandler(),
        );

        $this->assertSame(206, $response->getStatusCode());
        $this->assertStringContainsString('attachment;', $response->getHeaderLine('Content-Disposition'));
    }

    public function testHeadRequestWithRangeHasThePartialHeadersButNoBody(): void
    {
        file_put_contents($this->mediaDir . '/clip.mp4', 'abcdefghijklm');

        $response = $this->createMiddleware()->process(
            $this->requestFor('media/clip.mp4')->withMethod('HEAD')->withHeader('Range', 'bytes=0-4'),
            $this->terminalHandler(),
        );

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('5', $response->getHeaderLine('Content-Length'));
        $this->assertSame('', (string)$response->getBody());
    }

    public function testHeadRequestHasTheHeadersButNoBody(): void
    {
        file_put_contents($this->mediaDir . '/photo.png', 'image content');

        $response = $this->createMiddleware()->process(
            $this->requestFor('media/photo.png')->withMethod('HEAD'),
            $this->terminalHandler(),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/png', $response->getHeaderLine('Content-Type'));
        $this->assertSame('13', $response->getHeaderLine('Content-Length'));
        $this->assertSame('', (string)$response->getBody());
    }

    public function testHtmlIsNeverServedInline(): void
    {
        file_put_contents($this->mediaDir . '/page.html', '<script>alert(1)</script>');

        $response = $this->createMiddleware()->process($this->requestFor('media/page.html'), $this->terminalHandler());

        $this->assertSame('application/octet-stream', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('attachment;', $response->getHeaderLine('Content-Disposition'));
        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
    }

    public function testDownloadParameterCountsByPresenceNotByValue(): void
    {
        file_put_contents($this->mediaDir . '/manual.pdf', '%PDF-1.4 test');

        $response = $this->createMiddleware()->process(
            $this->requestFor('media/manual.pdf', ['download' => '0']),
            $this->terminalHandler(),
        );

        $this->assertStringContainsString('attachment;', $response->getHeaderLine('Content-Disposition'));
    }

    // @deprecated Since 3.2.0, remove in 4.0 together with the legacy download route.
    public function testLegacyDownloadRouteRedirectsToTheMediaRoute(): void
    {
        $response = $this->createMiddleware()->process(
            $this->requestFor('download/' . rawurlencode('über.pdf')),
            $this->terminalHandler(),
        );

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('/media/' . rawurlencode('über.pdf') . '?download', $response->getHeaderLine('Location'));
    }

    // @deprecated Since 3.2.0, remove in 4.0 together with the legacy download route.
    public function testLegacyDownloadRedirectKeepsSubdirectoriesAndEncodedCharacters(): void
    {
        $route = 'download/sub/' . rawurlencode('a b#c?d.pdf') . '/x%2Fy.pdf';

        $response = $this->createMiddleware()->process($this->requestFor($route), $this->terminalHandler());

        $this->assertSame(
            '/media/sub/' . rawurlencode('a b#c?d.pdf') . '/x%2Fy.pdf?download',
            $response->getHeaderLine('Location'),
        );
    }

    // @deprecated Since 3.2.0, remove in 4.0 together with the legacy download route.
    public function testLegacyDownloadRedirectIncludesTheBaseUrl(): void
    {
        $middleware = $this->createMiddleware('/base/media', '/base/', '/base');

        $response = $middleware->process($this->requestFor('download/sub/photo.png'), $this->terminalHandler());

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('/base/media/sub/photo.png?download', $response->getHeaderLine('Location'));
    }

    public function testMediaRouteIsServedBelowABaseUrl(): void
    {
        file_put_contents($this->mediaDir . '/photo.png', 'image content');
        $middleware = $this->createMiddleware('/base/media', '/base/', '/base');

        $response = $middleware->process($this->requestFor('media/photo.png'), $this->terminalHandler());

        $this->assertSame('image/png', $response->getHeaderLine('Content-Type'));
    }

    private function createMiddleware(
        string $mediaUrl = '/media',
        string $webUrl = '/',
        string $baseUrl = '',
    ): MediaMiddleware {
        return new MediaMiddleware(
            new Alias(['@media' => $this->mediaDir]),
            new StreamFactory(),
            new ResponseFactory(),
            new UrlManager(
                new ServerRequest(),
                ['baseUrl' => $baseUrl, 'niceUrls' => true, 'scriptUrl' => '/index.php'],
            ),
            $mediaUrl,
            $webUrl,
        );
    }

    /**
     * Creates the file `clip.mp4` with a fixed modification time.
     *
     * @return array{string, string} The expected ETag and Last-Modified header
     */
    private function createClip(): array
    {
        $file = $this->mediaDir . '/clip.mp4';
        file_put_contents($file, 'abcdefghijklm');
        $mtime = 1700000000;
        touch($file, $mtime);
        clearstatcache();

        return [sprintf('"%x-%x"', $mtime, 13), gmdate('D, d M Y H:i:s', $mtime) . ' GMT'];
    }

    private function requestFor(string $route, array $query = []): ServerRequestInterface
    {
        return (new ServerRequest())
            ->withQueryParams($query)
            ->withAttribute(PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_ROUTE, $route);
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
        foreach (array_merge(glob($dir . '/*') ?: [], glob($dir . '/.[!.]*') ?: []) as $file) {
            is_dir($file) && !is_link($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($dir);
    }
}
