<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Config;
use herbie\plugins\markdown\MarkdownPlugin;
use InvalidArgumentException;
use League\CommonMark\Extension\SmartPunct\SmartPunctExtension;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class MarkdownPluginTest extends Unit
{
    public function testCommonMarkFlavorIsDefault(): void
    {
        $plugin = $this->createPlugin();

        $this->assertSame('<h1>Title</h1>', $plugin->parse('# Title'));
    }

    public function testCommonMarkFlavorDoesNotParseTables(): void
    {
        $plugin = $this->createPlugin('commonmark');

        $html = $plugin->parse("| a | b |\n|---|---|\n| 1 | 2 |");

        $this->assertStringNotContainsString('<table>', $html);
        $this->assertStringContainsString('| a | b |', $html);
    }

    public function testGfmFlavorParsesTables(): void
    {
        $plugin = $this->createPlugin('gfm');

        $html = $plugin->parse("| a | b |\n|---|---|\n| 1 | 2 |");

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<th>a</th>', $html);
    }

    public function testGfmFlavorParsesStrikethroughAndAutolinks(): void
    {
        $plugin = $this->createPlugin('gfm');

        $html = $plugin->parse('~~gone~~ and www.example.com');

        $this->assertStringContainsString('<del>gone</del>', $html);
        $this->assertStringContainsString('<a href="http://www.example.com">', $html);
    }

    public function testMarkdownExtraFlavorParsesAttributes(): void
    {
        $plugin = $this->createPlugin('markdown-extra');

        $html = $plugin->parse('# Title {#custom-id}');

        $this->assertStringContainsString('id="custom-id"', $html);
    }

    public function testMarkdownExtraFlavorParsesFootnotes(): void
    {
        $plugin = $this->createPlugin('markdown-extra');

        $html = $plugin->parse("Text[^1]\n\n[^1]: The note.");

        $this->assertStringContainsString('footnote', $html);
        $this->assertStringContainsString('The note.', $html);
    }

    public function testDeprecatedMarkdownExtraFlavorLogsWarning(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('markdown-extra'));

        $plugin = $this->createPlugin('markdown-extra', $logger);
        $plugin->parse('# Title');
    }

    public function testUnknownFlavorFallsBackToCommonMark(): void
    {
        $plugin = $this->createPlugin('not-existing-flavor');

        $html = $plugin->parse("| a | b |\n|---|---|\n| 1 | 2 |");

        $this->assertStringNotContainsString('<table>', $html);
    }

    public function testConfiguredExtensionIsAddedToCommonmark(): void
    {
        $plugin = $this->createPlugin(extensions: [SmartPunctExtension::class]);

        $html = $plugin->parse('"Hi" -- ...');

        $this->assertStringContainsString('“Hi”', $html);
        $this->assertStringContainsString('–', $html);
        $this->assertStringContainsString('…', $html);
    }

    public function testConfiguredExtensionIsAddedToFlavor(): void
    {
        $plugin = $this->createPlugin('gfm', extensions: [SmartPunctExtension::class]);

        $html = $plugin->parse("| a | b |\n|---|---|\n| 1 | 2 |\n\n\"Hi\"");

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('“Hi”', $html);
    }

    public function testExtensionOptionsArePassedThrough(): void
    {
        $plugin = $this->createPlugin(
            extensions: [SmartPunctExtension::class],
            commonmarkOptions: [
                'smartpunct' => [
                    'double_quote_opener' => '«',
                    'double_quote_closer' => '»',
                ],
            ],
        );

        $this->assertStringContainsString('«Hi»', $plugin->parse('"Hi"'));
    }

    public function testUnknownExtensionClassThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Not\An\Extension');

        $plugin = $this->createPlugin(extensions: ['Not\An\Extension']);
        $plugin->parse('# Title');
    }

    public function testClassWithoutExtensionInterfaceThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $plugin = $this->createPlugin(extensions: [\stdClass::class]);
        $plugin->parse('# Title');
    }

    public function testRawHtmlBlockIsKept(): void
    {
        $plugin = $this->createPlugin();

        $html = $plugin->parse("<div class=\"box\">\n\n<p>Text</p>\n\n</div>");

        $this->assertStringContainsString('<div class="box">', $html);
        $this->assertStringContainsString('<p>Text</p>', $html);
        $this->assertStringNotContainsString('&lt;div', $html);
    }

    private function createPlugin(
        string $flavor = 'commonmark',
        ?LoggerInterface $logger = null,
        array $extensions = [],
        array $commonmarkOptions = [],
    ): MarkdownPlugin {
        $config = new Config([
            'plugins' => [
                'markdown' => [
                    'apiVersion' => 2,
                    'pluginName' => 'markdown',
                    'enableTwigFilter' => true,
                    'enableTwigFunction' => true,
                    'flavor' => $flavor,
                    'extensions' => $extensions,
                    'commonmark' => array_merge([
                        'html_input' => 'allow',
                        'allow_unsafe_links' => true,
                    ], $commonmarkOptions),
                ],
            ],
        ]);

        return new MarkdownPlugin($config, $logger ?? new NullLogger());
    }
}
