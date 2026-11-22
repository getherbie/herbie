<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\plugins\search\SearchPlugin;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class SearchPluginTest extends Unit
{
    public function testExtractSearchTermsSplitsOnWhitespace(): void
    {
        $plugin = $this->createUninitializedPlugin();

        $this->assertSame(['herbie'], $this->invokePrivate($plugin, 'extractSearchTerms', ['herbie']));
        $this->assertSame(['herbie', 'php'], $this->invokePrivate($plugin, 'extractSearchTerms', ['herbie php']));
        $this->assertSame(['herbie', 'php'], $this->invokePrivate($plugin, 'extractSearchTerms', ['  herbie   php  ']));
    }

    public function testExtractSearchTermsHandlesEmptyInput(): void
    {
        $plugin = $this->createUninitializedPlugin();

        $this->assertSame([], $this->invokePrivate($plugin, 'extractSearchTerms', ['']));
        $this->assertSame([], $this->invokePrivate($plugin, 'extractSearchTerms', ['   ']));
    }

    public function testMatchNonExactIsCaseInsensitiveSubstring(): void
    {
        $plugin = $this->createUninitializedPlugin();

        $this->assertTrue($this->invokePrivate($plugin, 'match', ['herbie', ['herbie'], ['Some content with HERBIE rocks'], false]));
        $this->assertTrue($this->invokePrivate($plugin, 'match', ['herbie php', ['herbie', 'php'], ['herbie and php'], false]));
        $this->assertFalse($this->invokePrivate($plugin, 'match', ['herbie php', ['herbie', 'php'], ['herbie only'], false]));
    }

    public function testMatchNonExactRequiresAllTerms(): void
    {
        $plugin = $this->createUninitializedPlugin();

        $this->assertFalse($this->invokePrivate($plugin, 'match', ['herbie rocks', ['herbie', 'rocks'], ['herbie only'], false]));
    }

    public function testMatchExactMatchesSubstringPhrase(): void
    {
        $plugin = $this->createUninitializedPlugin();

        // Exact mode is phrase-based (no word boundaries): "herbie" matches "herbieRocks".
        $this->assertTrue($this->invokePrivate($plugin, 'match', ['herbie', ['herbie'], ['herbieRocks'], true]));
        $this->assertTrue($this->invokePrivate($plugin, 'match', ['herbie', ['herbie'], ['herbie rocks'], true]));
    }

    public function testMatchExactIsCaseInsensitive(): void
    {
        $plugin = $this->createUninitializedPlugin();

        $this->assertTrue($this->invokePrivate($plugin, 'match', ['herbie', ['herbie'], ['Herbie rocks'], true]));
    }

    public function testMatchExactRequiresContiguousPhrase(): void
    {
        $plugin = $this->createUninitializedPlugin();

        // Scattered terms: non-exact matches, exact does not.
        $this->assertTrue($this->invokePrivate($plugin, 'match', ['above commands', ['above', 'commands'], ['above something commands'], false]));
        $this->assertFalse($this->invokePrivate($plugin, 'match', ['above commands', ['above', 'commands'], ['above something commands'], true]));

        // Contiguous phrase: both match.
        $this->assertTrue($this->invokePrivate($plugin, 'match', ['above commands', ['above', 'commands'], ['above commands here'], true]));
    }

    public function testBuildSnippetHighlightsSingleTerm(): void
    {
        $plugin = $this->createUninitializedPlugin();

        $snippet = $this->invokePrivate($plugin, 'buildSnippet', ['This is herbie content.', 'herbie', ['herbie'], false]);

        $this->assertStringContainsString('<mark>herbie</mark>', $snippet);
    }

    public function testBuildSnippetHighlightsAllTerms(): void
    {
        $plugin = $this->createUninitializedPlugin();

        $snippet = $this->invokePrivate($plugin, 'buildSnippet', ['herbie rocks the search', 'herbie rocks', ['herbie', 'rocks'], false]);

        $this->assertStringContainsString('<mark>herbie</mark>', $snippet);
        $this->assertStringContainsString('<mark>rocks</mark>', $snippet);
    }

    public function testBuildSnippetExactHighlightsWholePhrase(): void
    {
        $plugin = $this->createUninitializedPlugin();

        $snippet = $this->invokePrivate($plugin, 'buildSnippet', ['For the above commands here.', 'above commands', ['above', 'commands'], true]);

        $this->assertStringContainsString('<mark>above commands</mark>', $snippet);
    }

    public function testBuildSnippetNormalizesWhitespace(): void
    {
        $plugin = $this->createUninitializedPlugin();

        $snippet = $this->invokePrivate($plugin, 'buildSnippet', ['<p>Alpha</p><p>Beta</p>', 'Alpha', ['Alpha'], false]);

        $this->assertStringNotContainsString('AlphaBeta', $snippet);
        $this->assertStringContainsString('Alpha', $snippet);
    }

    private function createPluginWithCache(\Psr\SimpleCache\CacheInterface $cache, bool $useCache): SearchPlugin
    {
        $plugin = (new ReflectionClass(SearchPlugin::class))->newInstanceWithoutConstructor();
        $this->setPrivateProperty($plugin, 'cache', $cache);
        $this->setPrivateProperty($plugin, 'config', new \herbie\Config(['plugins' => ['search' => ['useCache' => $useCache]]]));
        $this->setPrivateProperty($plugin, 'eventManager', new \herbie\EventManager());
        $this->setPrivateProperty($plugin, 'urlManager', new \herbie\UrlManager(new \Laminas\Diactoros\ServerRequest(), ['niceUrls' => false]));
        return $plugin;
    }

    private function setPrivateProperty(SearchPlugin $plugin, string $propertyName, mixed $value): void
    {
        $property = new ReflectionProperty(SearchPlugin::class, $propertyName);
        $property->setValue($plugin, $value);
    }

    public function testLoadPageDataUsesCacheOnHit(): void
    {
        $cache = $this->createMock(\Psr\SimpleCache\CacheInterface::class);
        $cache->method('get')->willReturn('cached content');
        $cache->expects($this->never())->method('set');

        $plugin = $this->createPluginWithCache($cache, true);
        $page = new \herbie\Page(['title' => 'Test'], ['default' => 'hello']);

        $result = $this->invokePrivate($plugin, 'loadPageData', [$page, 'token1']);
        $this->assertSame(['Test', 'cached content'], $result);
    }

    public function testLoadPageDataRendersAndCachesOnMiss(): void
    {
        $cache = $this->createMock(\Psr\SimpleCache\CacheInterface::class);
        $cache->method('get')->willReturn(null);
        $cache->expects($this->exactly(2))->method('set');

        $plugin = $this->createPluginWithCache($cache, true);
        $page = new \herbie\Page(['title' => 'Test'], ['default' => 'hello']);

        $result = $this->invokePrivate($plugin, 'loadPageData', [$page, 'token1']);
        $this->assertSame(['Test', 'hello'], $result);
    }

    public function testLoadPageDataSkipsCacheWhenDisabled(): void
    {
        $cache = $this->createMock(\Psr\SimpleCache\CacheInterface::class);
        $cache->expects($this->never())->method('get');
        $cache->expects($this->never())->method('set');

        $plugin = $this->createPluginWithCache($cache, false);
        $page = new \herbie\Page(['title' => 'Test'], ['default' => 'hello']);

        $result = $this->invokePrivate($plugin, 'loadPageData', [$page, 'token1']);
        $this->assertSame(['Test', 'hello'], $result);
    }

    public function testCacheInvalidatesOnTokenChange(): void
    {
        $cache = $this->createMock(\Psr\SimpleCache\CacheInterface::class);
        $store = [];
        $cache->method('get')->willReturnCallback(static function (string $key) use (&$store) {
            return $store[$key] ?? null;
        });
        $cache->method('set')->willReturnCallback(static function (string $key, $value) use (&$store): bool {
            $store[$key] = $value;
            return true;
        });
        $cache->method('delete')->willReturnCallback(static function (string $key) use (&$store): bool {
            unset($store[$key]);
            return true;
        });

        $plugin = $this->createPluginWithCache($cache, true);
        $page = new \herbie\Page(['title' => 'Test'], ['default' => 'hello']);

        // First call with token A -> miss, renders, caches.
        $this->invokePrivate($plugin, 'loadPageData', [$page, 'tokenA']);
        // Second call with same token -> hit, no re-render.
        $r2 = $this->invokePrivate($plugin, 'loadPageData', [$page, 'tokenA']);
        $this->assertSame('hello', $r2[1]);
        // Third call with different token -> miss (new key), re-renders.
        $r3 = $this->invokePrivate($plugin, 'loadPageData', [$page, 'tokenB']);
        $this->assertSame('hello', $r3[1]);

        // Two distinct cache keys were used (invalidation via token change).
        $this->assertCount(2, $store);
    }

    private function createUninitializedPlugin(): SearchPlugin
    {
        return (new ReflectionClass(SearchPlugin::class))->newInstanceWithoutConstructor();
    }

    private function invokePrivate(SearchPlugin $plugin, string $methodName, array $arguments): mixed
    {
        $method = new ReflectionMethod(SearchPlugin::class, $methodName);
        return $method->invokeArgs($plugin, $arguments);
    }
}
