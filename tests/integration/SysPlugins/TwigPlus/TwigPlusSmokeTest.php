<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigPlus;

use herbie\TwigRenderer;

final class TwigPlusSmokeTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    private function twig(): TwigRenderer
    {
        return $this->tester->initTwigRenderer(
            dirname(__DIR__, 4),
            dirname(__DIR__, 2) . '/Fixtures/site'
        );
    }

    public function testAsciiTreeFunction(): void
    {
        $expected = <<<STRING
        ├ Index
        └ Alpha Index
          └ Alpha Delta\n
        STRING;
        $actual = $this->twig()->renderString('{{ ascii_tree() }}');
        $this->assertEquals($expected, $actual);
    }

    public function testBreadcrumbFunction(): void
    {
        $expected = '<ul class="breadcrumb"><li><a href="/">Index</a></li></ul>';
        $actual = $this->twig()->renderString('{{ breadcrumb() }}');
        $this->assertEquals($expected, $actual);
    }

    public function testListingFunction(): void
    {
        $expected = '<div class="listing"><section><article><h2><span class="link link--internal"><a href="/alpha" class="link__label">Alpha Index</a></span></h2><p></p></article></section><nav class="pagination"></nav></div>';
        $actual = $this->twig()->renderString('{{ listing(filter="route|alpha") }}');
        $this->assertEquals($expected, $actual);
    }

    public function testMenuFunction(): void
    {
        $expected = '<div class="menu"><ul><li class="current"><a href="/">Index</a></li><li><a href="/alpha">Alpha Index</a></ul></div>';
        $actual = $this->twig()->renderString('{{ menu(maxDepth=0) }}');
        $this->assertEquals($expected, $actual);
    }

    public function testPageTaxonomiesFunction(): void
    {
        $expected = '<div class="blog-meta"></div>';
        $actual = $this->twig()->renderString('{{ page_taxonomies() }}');
        $this->assertEquals($expected, $actual);
    }

    public function testPagerFunction(): void
    {
        $expected = '<div class="pager">'
            . '<a href="/zeta/psi"class="pager-link-next"><span class="pager-label-next">Zeta Psi</span></a>'
            . '</div>';
        $actual = $this->twig()->renderString('{{ pager() }}');
        $this->assertEquals($expected, $actual);
    }

    public function testPagesFilteredFunction(): void
    {
        $expected = '';
        $actual = $this->twig()->renderString('{{ pages_filtered([]) }}');
        $this->assertEquals($expected, $actual);
    }

    public function testPagesRecentFunction(): void
    {
        $expected = '<div class="widget-blog widget-blog-recent-posts"><h4>Recent posts</h4><ul><li><span class="link link--internal"><a href="/" class="link__label">Index</a></span></li></ul></div>';
        $actual = $this->twig()->renderString('{{ pages_recent(limit=1) }}');
        $this->assertEquals($expected, $actual);
    }

    public function testPageTitleFunction(): void
    {
        $expected = 'Index';
        $actual = $this->twig()->renderString('{{ page_title() }}');
        $this->assertEquals($expected, $actual);
    }

    public function testSitemapFunction(): void
    {
        $expected = '<div class="sitemap"><ul><li class="current"><a href="/">Index</a></li><li><a href="/alpha">Alpha Index</a></ul></div>';
        $actual = $this->twig()->renderString('{{ sitemap(maxDepth=0) }}');
        $this->assertEquals($expected, $actual);
    }

    public function testTaxonomyArchiveFunction(): void
    {
        $expected = '';
        $actual = $this->twig()->renderString('{{ taxonomy_archive() }}');
        $this->assertEquals($expected, $actual);
    }

    public function testTaxonomyAuthorsFunction(): void
    {
        $expected = '';
        $actual = $this->twig()->renderString('{{ taxonomy_authors() }}');
        $this->assertEquals($expected, $actual);
    }

    public function testTaxonomyCategoriesFunction(): void
    {
        $expected = '';
        $actual = $this->twig()->renderString('{{ taxonomy_categories() }}');
        $this->assertEquals($expected, $actual);
    }

    public function testTaxonomyTagsFunction(): void
    {
        $expected = '';
        $actual = $this->twig()->renderString('{{ taxonomy_tags() }}');
        $this->assertEquals($expected, $actual);
    }
}
