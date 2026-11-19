<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins\TwigCore\Functions;

use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class MailLinkFunctionTest extends Unit
{
    protected UnitTester $tester;

    public function testLinkWithoutParams(): void
    {
        $this->expectException(SyntaxError::class);
        $twig = '{{ link_mail() }}';
        $this->twig()->renderString($twig);
    }

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site',
        );
    }

    public function testLinkWithEmail(): void
    {
        $expected = $this->getHtml(
            '<a class="link__label" href="mailto:me@example.com">me@example.com</a>',
        );
        $twig = '{{ link_mail("me@example.com") }}';
        $actual = $this->twig()->renderString($twig);
        $this->assertSame($expected, $actual);
    }

    private function getHtml(string $link): string
    {
        return "<span class=\"link link--mailto\">\n    {$link}\n</span>\n";
    }

    public function testLinkWithEmailAndLabel(): void
    {
        $expected = $this->getHtml(
            '<a class="link__label" href="mailto:me@example.com">Example</a>',
        );
        $twig = '{{ link_mail("me@example.com", "Example") }}';
        $actual = $this->twig()->renderString($twig);
        $this->assertSame($expected, $actual);
    }

    public function testLinkWithEmailLabelAndAttribs(): void
    {
        $expected = $this->getHtml(
            '<a class="link-class" href="mailto:me@example.com" id="link-id">Example</a>',
        );
        $twig = '{{ link_mail("me@example.com", "Example", {class:"link-class", id:"link-id"}) }}';
        $actual = $this->twig()->renderString($twig);
        $this->assertSame($expected, $actual);
    }

    public function testLinkWithCustomTemplate(): void
    {
        $expected = 'me@example.com';
        $twig = '{{ link_mail("me@example.com", template="{{label}}") }}';
        $actual = $this->twig()->renderString($twig);
        $this->assertSame($expected, $actual);
    }

    public function testLinkWithNotExistingCustomTemplate(): void
    {
        $this->expectException(LoaderError::class);
        $twig = '{{ link_mail("me@example.com", template="@not/existing/template.twig") }}';
        $this->twig()->renderString($twig);
    }
}
