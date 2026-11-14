<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\UrlManager;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;

final class UrlManagerTest extends Unit
{
    private function makeRequest(string $path): \Psr\Http\Message\ServerRequestInterface
    {
        return (new ServerRequest())->withUri(new Uri('http://example.com' . $path));
    }

    public function testParseRequestReturnsRouteAndParams(): void
    {
        $request = $this->makeRequest('/foo/bar');
        $um = new UrlManager($request, ['niceUrls' => false, 'baseUrl' => '', 'scriptUrl' => '']);
        [$route, $params] = $um->parseRequest();
        $this->assertSame('foo/bar', $route);
        $this->assertSame([], $params);
    }

    public function testParseRequestIsCached(): void
    {
        $request = $this->makeRequest('/hello');
        $um = new UrlManager($request, ['niceUrls' => false]);
        $first = $um->parseRequest();
        $second = $um->parseRequest();
        $this->assertSame($first, $second);
    }

    public function testParseRequestRuleMatchCachesResult(): void
    {
        $request = $this->makeRequest('/user/42');
        $um = new UrlManager($request, [
            'niceUrls' => true,
            'baseUrl' => '',
            'scriptUrl' => '',
            'rules' => [
                ['user/{id}', 'user/show', ['id' => '[0-9]+']],
            ],
        ]);
        [$route, $params] = $um->parseRequest();
        $this->assertSame('user/show', $route);
        $this->assertSame(['id' => '42'], $params);

        // second call must return cached (same) result, not re-parse
        [$route2, $params2] = $um->parseRequest();
        $this->assertSame($route, $route2);
        $this->assertSame($params, $params2);
    }

    public function testCleanPathStripsScriptUrl(): void
    {
        $request = $this->makeRequest('/index.php/foo');
        $um = new UrlManager($request, ['scriptUrl' => '/index.php', 'niceUrls' => false]);
        [$route, $params] = $um->parseRequest();
        $this->assertSame('foo', $route);
    }

    public function testCreateUrlNiceUrls(): void
    {
        $request = $this->makeRequest('/');
        $um = new UrlManager($request, ['niceUrls' => true, 'baseUrl' => '/base', 'scriptUrl' => '']);
        $this->assertSame('/base/foo', $um->createUrl('foo'));
    }

    public function testCreateUrlNonNiceUrls(): void
    {
        $request = $this->makeRequest('/');
        $um = new UrlManager($request, ['niceUrls' => false, 'baseUrl' => '', 'scriptUrl' => '/index.php']);
        $this->assertSame('/index.php/foo', $um->createUrl('foo'));
    }
}
