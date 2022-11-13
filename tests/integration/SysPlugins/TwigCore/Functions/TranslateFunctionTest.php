<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Functions;

use herbie\TwigRenderer;
use UnitTester;

final class TranslateFunctionTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 5),
            dirname(__DIR__, 3) . '/Fixtures/site'
        );
    }

    public function testTranslateWithWrongParams(): void
    {
        $this->assertSame('', $this->twig()->renderString('{{ translate() }}'));
        $this->assertSame('', $this->twig()->renderString('{{ translate("", "", params={a:1,b:2}) }}'));
        $this->assertSame('', $this->twig()->renderString('{{ translate("app") }}'));
        $this->assertSame('test', $this->twig()->renderString('{{ translate("", "test") }}'));
    }

    public function testTranslateFromApp(): void
    {
        $this->assertSame('Herbie CMS ist grossartig!', $this->twig()->renderString('{{ translate("app", "herbieIsGreat") }}'));
    }

    public function testTranslateFromPlugin(): void
    {
        $this->assertSame('Beispiel-Übersetzung', $this->twig()->renderString('{{ translate("dummy", "exampleTranslation") }}'));
    }

    public function testTranslateFromPluginWithParams(): void
    {
        $actual = $this->twig()->renderString(
            '{{ translate("dummy", "exampleTranslationWithParamsOneAndTwo", {one:"ABC123", two: "ÄÖÜäöü"}) }}'
        );
        $this->assertSame('Beispiel-Übersetzung mit Parameter ABC123 and ÄÖÜäöü', $actual);
    }
}
