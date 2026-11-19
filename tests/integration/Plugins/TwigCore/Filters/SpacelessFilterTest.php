<?php

declare(strict_types=1);

namespace herbie\tests\integration\Plugins\TwigCore\Filters;

use Codeception\Test\Unit;
use herbie\Application;
use herbie\ApplicationPaths;
use herbie\TwigRenderer;

final class SpacelessFilterTest extends Unit
{
    protected TwigRenderer $twigRenderer;

    public function testSpaceless(): void
    {
        $this->assertSame(
            '<div><strong>foo</strong></div>',
            $this->twigRenderer->renderString('{{ "<div>   <strong>foo</strong>   </div>"|spaceless }}'),
        );

        $this->assertSame(
            '<p>a</p><p>b</p>',
            $this->twigRenderer->renderString('{{ "<p>a</p>   <p>b</p>"|spaceless }}'),
        );

        $this->assertSame('', $this->twigRenderer->renderString('{{ ""|spaceless }}'));
        $this->assertSame('', $this->twigRenderer->renderString('{{ null|spaceless }}'));
    }

    protected function _setUp(): void
    {
        $app = new Application(
            new ApplicationPaths(
                dirname(__DIR__, 5),
                dirname(__DIR__, 3) . '/Fixtures/site',
                dirname(__DIR__, 5) . '/vendor',
                dirname(__DIR__, 4) . '/_data/web',
            ),
        );
        $app->getPluginManager()->init();
        $app->getTwigRenderer()->init();
        $this->twigRenderer = $app->getTwigRenderer();
    }
}
