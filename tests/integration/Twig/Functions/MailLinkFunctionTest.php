<?php

declare(strict_types=1);

namespace Tests\Unit\Twig\Functions;

use ArgumentCountError;
use herbie\Application;
use herbie\TwigRenderer;

final class MailLinkFunctionTest extends \Codeception\Test\Unit
{
    protected TwigRenderer $twigRenderer;
    
    protected function _setUp(): void
    {
        $app = new Application(dirname(__DIR__, 2) . '/Fixtures/site', dirname(__DIR__, 4) . '/vendor');
        ($this->twigRenderer = $app->getTwigRenderer())->init();
    }

    public function testLinkWithoutParams(): void
    {
        $this->expectException(ArgumentCountError::class);
        $twig = '{{ mail_link() }}';
        $this->twigRenderer->renderString($twig);
    }

    public function testLinkWithEmail(): void
    {
        $expected = $this->getHtml(
            '<a class="link__label" href="mailto&#x3A;me&#x40;example.com">me@example.com</a>'
        );
        $twig = '{{ mail_link("me@example.com") }}';
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame($expected, $actual);
    }

    public function testLinkWithEmailAndLabel(): void
    {
        $expected = $this->getHtml(
            '<a class="link__label" href="mailto&#x3A;me&#x40;example.com">Example</a>'
        );
        $twig = '{{ mail_link("me@example.com", "Example") }}';
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame($expected, $actual);
    }

    public function testLinkWithEmailLabelAndAttribs(): void
    {
        $expected = $this->getHtml(
            '<a class="link-class" href="mailto&#x3A;me&#x40;example.com" id="link-id">Example</a>'
        );
        $twig = '{{ mail_link("me@example.com", "Example", {class:"link-class", id:"link-id"}) }}';
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame($expected, $actual);
    }

    public function testLinkWithCustomTemplate(): void
    {
        $expected = 'me@example.com';
        $twig = '{{ mail_link("me@example.com", template="{{label}}") }}';
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame($expected, $actual);
    }
    
    public function testLinkWithNotExistingCustomTemplate(): void
    {
        $this->expectException(\Twig\Error\LoaderError::class);
        $twig = '{{ mail_link("me@example.com", template="@not/existing/template.twig") }}';
        $this->twigRenderer->renderString($twig);
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
