<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class HtmlAttributesFunctionTest extends Unit
{
    protected UnitTester $tester;

    public function testEmptyAttribs(): void
    {
        $this->assertSame('', $this->twig()->renderString('{{ html_attributes({}) }}'));
    }

    public function testSimpleAttribs(): void
    {
        $this->assertSame(
            ' class="foo" id="bar"',
            $this->twig()->renderString('{{ html_attributes({class: "foo", id: "bar"}) }}'),
        );
    }

    public function testNullAndFalseAreOmitted(): void
    {
        $this->assertSame(
            ' id="bar"',
            $this->twig()->renderString('{{ html_attributes({class: null, disabled: false, id: "bar"}) }}'),
        );
    }

    public function testTrueRendersBooleanAttribute(): void
    {
        $this->assertSame(
            ' disabled',
            $this->twig()->renderString('{{ html_attributes({disabled: true}) }}'),
        );
    }

    public function testEscapesAttributeNames(): void
    {
        $this->assertSame(
            ' foo&#x20;bar="baz"',
            $this->twig()->renderString('{{ html_attributes({"foo bar": "baz"}) }}'),
        );
    }

    public function testHtmlAttrEscapesSpecialChars(): void
    {
        $this->assertSame(
            ' class="a&#x20;b" data-url="&#x2F;x&#x3A;y&#x40;z"',
            $this->twig()->renderString('{{ html_attributes({class: "a b", "data-url": "/x:y@z"}) }}'),
        );
    }

    public function testHtmlStrategyKeepsSlashes(): void
    {
        $this->assertSame(
            ' src="/media/x.gif"',
            $this->twig()->renderString('{{ html_attributes({src: "/media/x.gif"}, "html") }}'),
        );
    }

    public function testHtmlStrategyEscapesQuotes(): void
    {
        $this->assertSame(
            ' title="a&quot;b"',
            $this->twig()->renderString('{{ html_attributes({title: "a\\"b"}, "html") }}'),
        );
    }

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site',
        );
    }
}
