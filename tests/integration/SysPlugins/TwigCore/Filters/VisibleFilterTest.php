<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Filters;

use herbie\Application;
use herbie\TwigRenderer;

final class VisibleFilterTest extends \Codeception\Test\Unit
{
    protected TwigRenderer $twigRenderer;

    protected function _setUp(): void
    {
        $app = new Application(dirname(__DIR__, 3) . '/Fixtures/site', dirname(__DIR__, 5) . '/vendor');
        $app->getPluginManager()->init();
        $app->getTwigRenderer()->init();
        $this->twigRenderer = $app->getTwigRenderer();
    }
    
    public function testUnfilteredTree()
    {
        $twig = <<<TWIG
            {%- for item1 in site.pageTree -%}
                *{{- item1 -}}
                {%- for item2 in item1.getChildren() -%}
                    **{{- item2 -}}
                    {%- for item3 in item2.getChildren() -%}
                        ***{{- item3 -}}
                    {%- endfor -%}            
                {%- endfor -%}
            {%- endfor -%}
        TWIG;
        $expected = '*Index*Zeta Index**Zeta Psi**Zeta Beta*Omega Index**Omega Gamma'
            . '*Alpha Index**Alpha Delta**Alpha Sigma*Page Data*Segments';
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame($expected, $actual);
    }

    public function testFilteredTree()
    {
        $twig = <<<TWIG
            {%- for item1 in site.pageTree|visible -%}
                *{{- item1 -}}
                {%- for item2 in item1.getChildren() -%}
                    **{{- item2 -}}
                    {%- for item3 in item2.getChildren() -%}
                        ***{{- item3 -}}
                    {%- endfor -%}            
                {%- endfor -%}
            {%- endfor -%}
        TWIG;
        $expected = '*Index*Alpha Index**Alpha Delta**Alpha Sigma';
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame($expected, $actual); // TODO fix this test, the expected result is simply wrong
    }
}
