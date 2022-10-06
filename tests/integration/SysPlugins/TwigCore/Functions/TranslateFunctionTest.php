<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Functions;

use herbie\Application;

final class TranslateFunctionTest extends \Codeception\Test\Unit
{
    protected Application $app;

    protected function _setUp(): void
    {
        $app = new Application(dirname(__DIR__, 3) . '/Fixtures/site', dirname(__DIR__, 5) . '/vendor');
        $app->getPluginManager()->init();
        $app->getTwigRenderer()->init();
        $app->getTranslator()->init();
        $this->app = $app;
    }

    private function _render(string $twig): string
    {
        return $this->app->getTwigRenderer()->renderString($twig);
    }

    public function testTranslateWithWrongParams(): void
    {
        $this->assertSame('', $this->_render('{{ translate() }}'));
        $this->assertSame('', $this->_render('{{ translate("", "", params={a:1,b:2}) }}'));
        $this->assertSame('', $this->_render('{{ translate("app") }}'));
        $this->assertSame('test', $this->_render('{{ translate("", "test") }}'));
    }

    public function testTranslateFromApp(): void
    {
        $this->assertSame('Herbie CMS ist grossartig!', $this->_render('{{ translate("app", "Herbie is great!") }}'));
    }

    public function testTranslateFromPlugin(): void
    {
        $this->assertSame('Dummy-Übersetzung', $this->_render('{{ translate("dummy", "Dummy translation") }}'));
    }

    public function testTranslateFromPluginWithParams(): void
    {
        $actual = $this->_render(
            '{{ translate("dummy", "Dummy translation with params {one} and {two}", {one:"ABC123", two: "ÄÖÜäöü"}) }}'
        );
        $this->assertSame('Dummy-Übersetzung mit Parameter ABC123 and ÄÖÜäöü', $actual);
    }
}
