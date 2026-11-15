<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Config;
use herbie\sysplugins\markdown\MarkdownSysPlugin;
use Psr\Log\NullLogger;

final class MarkdownSysPluginTest extends Unit
{
    public function testCommonMarkFlavorIsDefault(): void
    {
        $plugin = $this->createPlugin();

        $this->assertSame('<h1>Title</h1>', $plugin->parseMarkdown('# Title'));
    }

    public function testCommonMarkFlavorDoesNotParseTables(): void
    {
        $plugin = $this->createPlugin('commonmark');

        $html = $plugin->parseMarkdown("| a | b |\n|---|---|\n| 1 | 2 |");

        $this->assertStringNotContainsString('<table>', $html);
        $this->assertStringContainsString('| a | b |', $html);
    }

    public function testGfmFlavorParsesTables(): void
    {
        $plugin = $this->createPlugin('gfm');

        $html = $plugin->parseMarkdown("| a | b |\n|---|---|\n| 1 | 2 |");

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<th>a</th>', $html);
    }

    public function testGfmFlavorParsesStrikethroughAndAutolinks(): void
    {
        $plugin = $this->createPlugin('gfm');

        $html = $plugin->parseMarkdown('~~gone~~ and www.example.com');

        $this->assertStringContainsString('<del>gone</del>', $html);
        $this->assertStringContainsString('<a href="http://www.example.com">', $html);
    }

    public function testMarkdownExtraFlavorParsesAttributes(): void
    {
        $plugin = $this->createPlugin('markdown-extra');

        $html = $plugin->parseMarkdown('# Title {#custom-id}');

        $this->assertStringContainsString('id="custom-id"', $html);
    }

    public function testMarkdownExtraFlavorParsesFootnotes(): void
    {
        $plugin = $this->createPlugin('markdown-extra');

        $html = $plugin->parseMarkdown("Text[^1]\n\n[^1]: The note.");

        $this->assertStringContainsString('footnote', $html);
        $this->assertStringContainsString('The note.', $html);
    }

    public function testUnknownFlavorFallsBackToCommonMark(): void
    {
        $plugin = $this->createPlugin('not-existing-flavor');

        $html = $plugin->parseMarkdown("| a | b |\n|---|---|\n| 1 | 2 |");

        $this->assertStringNotContainsString('<table>', $html);
    }

    public function testRawHtmlBlockIsKept(): void
    {
        $plugin = $this->createPlugin();

        $html = $plugin->parseMarkdown("<div class=\"box\">\n\n<p>Text</p>\n\n</div>");

        $this->assertStringContainsString('<div class="box">', $html);
        $this->assertStringContainsString('<p>Text</p>', $html);
        $this->assertStringNotContainsString('&lt;div', $html);
    }

    private function createPlugin(string $flavor = 'commonmark'): MarkdownSysPlugin
    {
        $config = new Config([
            'plugins' => [
                'markdown' => [
                    'apiVersion' => 2,
                    'pluginName' => 'markdown',
                    'enableTwigFilter' => true,
                    'enableTwigFunction' => true,
                    'flavor' => $flavor,
                    'commonmark' => [
                        'html_input' => 'allow',
                        'allow_unsafe_links' => true,
                    ],
                ],
            ],
        ]);

        return new MarkdownSysPlugin($config, new NullLogger());
    }
}
