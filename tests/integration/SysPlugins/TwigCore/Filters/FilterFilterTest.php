<?php

declare(strict_types=1);

namespace tests\integration\SysPlugins\TwigCore\Filters;

use herbie\Application;
use herbie\ApplicationPaths;
use herbie\TwigRenderer;

final class FilterFilterTest extends \Codeception\Test\Unit
{
    protected TwigRenderer $twigRenderer;

    protected function _setUp(): void
    {
        $app = new Application(new ApplicationPaths(dirname(__DIR__, 5), dirname(__DIR__, 3) . '/Fixtures/site'));
        $app->getPluginManager()->init();
        $app->getTwigRenderer()->init();
        $this->twigRenderer = $app->getTwigRenderer();
    }

    public function testFilterPageListWithoutSelectors()
    {
        $twig = <<<TWIG
            {%- for item in site.pageList|filter() -%}
                *{{- item.route -}}
            {%- endfor -%}
        TWIG;
        $expected = '**zeta/psi*zeta/beta*zeta*omega/gamma*omega*alpha*alpha/delta*alpha/sigma*pagedata*segments';
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame($expected, $actual);
    }

    public function testFilterPageListWithOneSelector()
    {
        $twig = <<<TWIG
            {%- for item in site.pageList|filter("route=alpha") -%}
                *{{- item.route -}}
            {%- endfor -%}
        TWIG;
        $expected = '*alpha';
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame($expected, $actual);
    }

    public function testFilterPageListWithSelectorsSortAndLimit()
    {
        $twig = <<<TWIG
            {%- for item in site.pageList|filter("type=page", "sort=-title", "limit=3") -%}
                *{{- item.title -}}
            {%- endfor -%}
        TWIG;
        $expected = '*Zeta Psi*Zeta Index*Zeta Beta';
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame($expected, $actual);
    }

    public function testFilterArray()
    {
        $twig = <<<TWIG
            {%- set arr = [{'key': 'abc'}, {'key': 'def'}, {'key': 'ghi'}] -%}
            {%- for item in arr|filter("key=def") -%}
                *{{- item.key -}}
            {%- endfor -%}
        TWIG;
        $expected = '*def';
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame($expected, $actual);
    }

    public function testFilterArrayWithDifferentOperators()
    {
        $twigArray = <<<TWIG
            {%- set array = [{key: 'abcd'}, {key: 'efgh'}, {key: 'ijkl'}, {key: 'mnop'}, {key: 'qrst'}, {key: 'uvwx'}] -%}    
        TWIG;

        // matchEqual
        $twig = <<<TWIG
            $twigArray
            {{- array|filter("key=mnop")|map(a => "#{a.key}")|join(',') -}}
        TWIG;
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame('mnop', $actual);

        // matchNotEqual
        $twig = <<<TWIG
            $twigArray
            {{- array|filter("key!=efgh")|map(a => "#{a.key}")|join(',') -}}
        TWIG;
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame('abcd,ijkl,mnop,qrst,uvwx', $actual);

        // matchGreaterThan
        $twig = <<<TWIG
            $twigArray
            {{- array|filter("key>mnop")|map(a => "#{a.key}")|join(',') -}}
        TWIG;
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame('qrst,uvwx', $actual);

        // matchGreaterThanEqual
        $twig = <<<TWIG
            $twigArray
            {{- array|filter("key>=mnop")|map(a => "#{a.key}")|join(',') -}}
        TWIG;
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame('mnop,qrst,uvwx', $actual);

        // matchLessThan
        $twig = <<<TWIG
            $twigArray
            {{- array|filter("key<mnop")|map(a => "#{a.key}")|join(',') -}}
        TWIG;
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame('abcd,efgh,ijkl', $actual);

        // matchLessThanEqual
        $twig = <<<TWIG
            $twigArray
            {{- array|filter("key<=mnop")|map(a => "#{a.key}")|join(',') -}}
        TWIG;
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame('abcd,efgh,ijkl,mnop', $actual);

        // matchContains
        $twig = <<<TWIG
            $twigArray
            {{- array|filter("key*=no")|map(a => "#{a.key}")|join(',') -}}
        TWIG;
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame('mnop', $actual);

        // matchStarts
        $twig = <<<TWIG
            $twigArray
            {{- array|filter("key^=m")|map(a => "#{a.key}")|join(',') -}}
        TWIG;
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame('mnop', $actual);

        // matchEnds
        $twig = <<<TWIG
            $twigArray
            {{- array|filter("key$=p")|map(a => "#{a.key}")|join(',') -}}
        TWIG;
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame('mnop', $actual);

        $words = <<<TWIG
            {%- set array = [{key: 'this is great'}, {key: 'not the same'}, {key: 'this is yours'}] -%}    
        TWIG;

        // matchContainsWords
        $twig = <<<TWIG
            $words
            {{- array|filter("key~=is")|map(a => "#{a.key}")|join(',') -}}
        TWIG;
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame('this is great,this is yours', $actual);

        // matchBitwiseAnd
        $twig = <<<TWIG
            {%- set array = [{key: 1}, {key: 2}, {key: 3}, {key: 4}, {key: 5}, {key: 6}, {key: 7}, {key: 8}] -%}
            {{- array|filter("key&2")|map(a => "#{a.key}")|join(',') -}}
        TWIG;
        $actual = $this->twigRenderer->renderString($twig);
        $this->assertSame('2,3,6,7', $actual);
    }
}
