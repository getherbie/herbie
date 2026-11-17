<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\sysplugins\markdown\RawHtmlProtector;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

final class RawHtmlProtectorTest extends Unit
{
    private RawHtmlProtector $protector;

    protected function _before(): void
    {
        $this->protector = new RawHtmlProtector();
    }

    public function testProtectReplacesBlockByPlaceholder(): void
    {
        $markdown = "Text before\n\n<div class=\"box\">\nContent\n</div>\n\nText after";
        $protected = $this->protector->protect($markdown);

        $this->assertStringNotContainsString('<div', $protected);
        $this->assertStringContainsString('Text before', $protected);
        $this->assertStringContainsString('Text after', $protected);
    }

    public function testRestoreRevertsPlaceholder(): void
    {
        $markdown = "<div class=\"box\">\nContent\n</div>";
        $html = $this->convert($this->protector->protect($markdown));

        $this->assertSame($markdown, $this->protector->restore($html));
    }

    public function testBlockWithEmptyLinesIsKeptIntact(): void
    {
        $markdown = "<div class=\"box\">\n\n<p>Paragraph</p>\n\n</div>";
        $html = $this->protector->restore($this->convert($this->protector->protect($markdown)));

        $this->assertSame($markdown, $html);
    }

    public function testTableInsideBlockIsNotParsed(): void
    {
        $markdown = "<div>\n| a | b |\n|---|---|\n| 1 | 2 |\n</div>";
        $html = $this->protector->restore($this->convert($this->protector->protect($markdown)));

        $this->assertStringNotContainsString('<th>', $html);
        $this->assertStringContainsString('| a | b |', $html);
    }

    public function testIndentedBlockIsNotProtected(): void
    {
        $markdown = "Example:\n\n    <div class=\"box\">\n    Content\n    </div>";
        $html = $this->protector->restore($this->convert($this->protector->protect($markdown)));

        $this->assertStringContainsString('<pre><code>', $html);
        $this->assertStringContainsString('&lt;div class=&quot;box&quot;&gt;', $html);
    }

    public function testNestedElementsAreCounted(): void
    {
        $markdown = "<div class=\"outer\">\n<div class=\"inner\">\nText\n</div>\n</div>";
        $html = $this->protector->restore($this->convert($this->protector->protect($markdown)));

        $this->assertSame($markdown, $html);
    }

    public function testMarkdownOutsideBlocksIsStillParsed(): void
    {
        $markdown = "# Title\n\n<div>\nRaw\n</div>\n\nSome *markdown* text.";
        $html = $this->protector->restore($this->convert($this->protector->protect($markdown)));

        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringContainsString('<em>markdown</em>', $html);
        $this->assertStringContainsString("<div>\nRaw\n</div>", $html);
    }

    public function testUnclosedBlockIsNotProtected(): void
    {
        $markdown = "<div class=\"box\">\nno closing tag";
        $html = $this->protector->restore($this->convert($this->protector->protect($markdown)));

        $this->assertStringContainsString('<div class="box">', $html);
    }

    public function testHtmlInsideFencedCodeBlockIsNotProtected(): void
    {
        $markdown = "```\n<div class=\"box\">\nContent\n</div>\n```";
        $protected = $this->protector->protect($markdown);

        // The HTML tags inside the fence must not be replaced by a placeholder.
        $this->assertStringContainsString('<div class="box">', $protected);
        $this->assertStringContainsString('```', $protected);
    }

    public function testHtmlInsideTildeFencedCodeBlockIsNotProtected(): void
    {
        $markdown = "~~~\n<div class=\"box\">\nContent\n</div>\n~~~";
        $protected = $this->protector->protect($markdown);

        $this->assertStringContainsString('<div class="box">', $protected);
        $this->assertStringContainsString('~~~', $protected);
    }

    private function convert(string $markdown): string
    {
        $environment = new Environment(['html_input' => 'allow']);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());
        $converter = new MarkdownConverter($environment);
        return trim($converter->convert($markdown)->getContent());
    }
}
