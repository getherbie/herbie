<?php

declare(strict_types=1);

namespace herbie\tests\integration\SysPlugins\TwigCore\Functions;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class SnippetFunctionTest extends Unit
{
    protected UnitTester $tester;

    public function testSnippetFunction(): void
    {
        $expected = 'Hi there';
        $actual = $this->twig()->renderString('{{ snippet("@site/snippets/test.twig") }}');
        $this->assertEquals($expected, $actual);
    }

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site'
        );
    }

    // TODO write more tests
}
