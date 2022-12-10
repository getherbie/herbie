<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Functions;

use ArgumentCountError;
use herbie\TwigRenderer;
use UnitTester;

final class MailLinkFunctionTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site'
        );
    }

    public function testLinkWithoutParams(): void
    {
        $this->expectException(ArgumentCountError::class);
        $twig = '{{ h_link_mail() }}';
        $this->twig()->renderString($twig);
    }

    public function testLinkWithEmail(): void
    {
        $expected = $this->getHtml(
            '<a class="link__label" href="mailto&#x3A;me&#x40;example.com">me@example.com</a>'
        );
        $twig = '{{ h_link_mail("me@example.com") }}';
        $actual = $this->twig()->renderString($twig);
        $this->assertSame($expected, $actual);
    }

    public function testLinkWithEmailAndLabel(): void
    {
        $expected = $this->getHtml(
            '<a class="link__label" href="mailto&#x3A;me&#x40;example.com">Example</a>'
        );
        $twig = '{{ h_link_mail("me@example.com", "Example") }}';
        $actual = $this->twig()->renderString($twig);
        $this->assertSame($expected, $actual);
    }

    public function testLinkWithEmailLabelAndAttribs(): void
    {
        $expected = $this->getHtml(
            '<a class="link-class" href="mailto&#x3A;me&#x40;example.com" id="link-id">Example</a>'
        );
        $twig = '{{ h_link_mail("me@example.com", "Example", {class:"link-class", id:"link-id"}) }}';
        $actual = $this->twig()->renderString($twig);
        $this->assertSame($expected, $actual);
    }

    public function testLinkWithCustomTemplate(): void
    {
        $expected = 'me@example.com';
        $twig = '{{ h_link_mail("me@example.com", template="{{label}}") }}';
        $actual = $this->twig()->renderString($twig);
        $this->assertSame($expected, $actual);
    }

    public function testLinkWithNotExistingCustomTemplate(): void
    {
        $twig = '{{ h_link_mail("me@example.com", template="@not/existing/template.twig") }}';
        $this->assertEquals('me@example.com', $this->twig()->renderString($twig));
    }

    private function getHtml(string $link): string
    {
        return <<<STRING
        <span class="link link--mailto">
            {$link}
        </span>
        STRING;
    }
}
