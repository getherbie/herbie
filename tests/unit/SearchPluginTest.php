<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\plugins\search\SearchPlugin;
use ReflectionClass;
use ReflectionMethod;

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

    public function testMatchExactRequiresWholeWord(): void
    {
        $plugin = $this->createUninitializedPlugin();

        $this->assertFalse($this->invokePrivate($plugin, 'match', ['herbie', ['herbie'], ['herbieRocks'], true]));
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
