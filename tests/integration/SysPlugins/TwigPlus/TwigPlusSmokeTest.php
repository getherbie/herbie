<?php

declare(strict_types=1);

namespace herbie\tests\integration\SysPlugins\TwigPlus;

use Codeception\Test\Unit;
use herbie\TwigRenderer;
use UnitTester;

final class TwigPlusSmokeTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testMenuAsciiTreeFunction(): void
    {
        $expected = <<<STRING
        ├ Index
        └ Alpha Index
          └ Alpha Delta\n
        STRING;
        $actual = $this->twig()->renderString('{{ menu_ascii_tree() }}');
        $this->assertEquals($expected, $actual);
    }

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 4),
            dirname(__DIR__, 2) . '/Fixtures/site'
        );
    }

    public function testMenuBreadcrumbFunction(): void
    {
        $expected = '<ul class="breadcrumb"><li><a href="/">Index</a></li></ul>';
        $actual = $this->twig()->renderString('{{ menu_breadcrumb() }}');
        $this->assertEquals($expected, $actual);
    }

    public function testMenuListFunction(): void
    {
        $expected = '<div class="listing"><section><article><h2><span class="link link--page"><a href="/alpha" class="link__label">Alpha Index</a></span></h2><p></p></article></section><nav class="pagination"></nav></div>';
        $actual = $this->twig()->renderString('{{ menu_list(filter="route|alpha") }}');
        $this->assertEquals($expected, $actual);
    }

    public function testMenuTreeFunction(): void
    {
        $expected = '<div class="menu"><ul><li class="current"><a href="/">Index</a></li><li><a href="/alpha">Alpha Index</a></ul></div>';
        $actual = $this->twig()->renderString('{{ menu_tree(depth=0) }}');
        $this->assertEquals($expected, $actual);
    }

    public function testMenuPagerFunction(): void
    {
        $expected = '<div class="pager">'
            . '<a href="/zeta/psi"class="pager-link-next"><span class="pager-label-next">Zeta Psi</span></a>'
            . '</div>';
        $actual = $this->twig()->renderString('{{ menu_pager() }}');
        $this->assertEquals($expected, $actual);
    }

    public function testMenuSitemapFunction(): void
    {
        $expected = '<div class="sitemap"><ul><li class="current"><a href="/">Index</a></li><li><a href="/alpha">Alpha Index</a></ul></div>';
        $actual = $this->twig()->renderString('{{ menu_sitemap(depth=0) }}');
        $this->assertEquals($expected, $actual);
    }
}
