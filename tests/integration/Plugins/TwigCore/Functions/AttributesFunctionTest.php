<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class AttributesFunctionTest extends Unit
{
    protected UnitTester $tester;

    public function testEmptyAttribs(): void
    {
        $this->assertSame('', $this->twig()->renderString('{{ attributes({}) }}'));
    }

    public function testSimpleAttribs(): void
    {
        $this->assertSame(
            ' class="foo" id="bar"',
            $this->twig()->renderString('{{ attributes({class: "foo", id: "bar"}) }}'),
        );
    }

    public function testHtmlAttrEscapesSpecialChars(): void
    {
        $this->assertSame(
            ' class="a&#x20;b" data-url="&#x2F;x&#x3A;y&#x40;z"',
            $this->twig()->renderString('{{ attributes({class: "a b", "data-url": "/x:y@z"}) }}'),
        );
    }

    public function testHtmlStrategyKeepsSlashes(): void
    {
        $this->assertSame(
            ' src="/media/x.gif"',
            $this->twig()->renderString('{{ attributes({src: "/media/x.gif"}, "html") }}'),
        );
    }

    public function testHtmlStrategyEscapesQuotes(): void
    {
        $this->assertSame(
            ' title="a&quot;b"',
            $this->twig()->renderString('{{ attributes({title: "a\\"b"}, "html") }}'),
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
