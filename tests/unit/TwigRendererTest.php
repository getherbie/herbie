<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Config;
use herbie\DataRepositoryInterface;
use herbie\EventManager;
use herbie\events\TwigInitializedEvent;
use herbie\Page;
use herbie\PageList;
use herbie\PageRepositoryInterface;
use herbie\Site;
use herbie\TwigRenderer;
use herbie\UrlManager;
use Laminas\Diactoros\ServerRequest;
use Psr\Log\NullLogger;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

final class TwigRendererTest extends Unit
{
    public function testRendererIsNotInitializedBeforeInit(): void
    {
        $renderer = $this->createRenderer();

        $this->assertFalse($renderer->isInitialized());
    }

    public function testInitMakesTheEnvironmentAvailable(): void
    {
        $renderer = $this->createRenderer();

        $renderer->init();

        $this->assertTrue($renderer->isInitialized());
        $this->assertSame($renderer->getTwigEnvironment(), $renderer->getTwigEnvironment());
    }

    public function testInitIsIdempotent(): void
    {
        $renderer = $this->createRenderer();

        $renderer->init();
        $environment = $renderer->getTwigEnvironment();
        $renderer->init();

        $this->assertSame($environment, $renderer->getTwigEnvironment());
    }

    public function testInitDispatchesTheTwigInitializedEvent(): void
    {
        $eventManager = new EventManager();
        $dispatched = false;
        $eventManager->addListener(TwigInitializedEvent::class, function () use (&$dispatched): void {
            $dispatched = true;
        });
        $renderer = $this->createRenderer($eventManager);

        $renderer->init();

        $this->assertTrue($dispatched);
    }

    public function testInitIsNotDispatchedTwice(): void
    {
        $eventManager = new EventManager();
        $count = 0;
        $eventManager->addListener(TwigInitializedEvent::class, function () use (&$count): void {
            $count++;
        });
        $renderer = $this->createRenderer($eventManager);

        $renderer->init();
        $renderer->init();

        $this->assertSame(1, $count);
    }

    public function testSiteIsAvailableAsGlobal(): void
    {
        $renderer = $this->createRenderer();

        $renderer->init();

        $this->assertSame('de', $renderer->renderString('{{ site.language }}'));
    }

    public function testTemplateIsRenderedWithContext(): void
    {
        $renderer = $this->createRenderer();

        $renderer->init();

        $this->assertSame('Hello Twig', $renderer->renderString('Hello {{ name }}', ['name' => 'Twig']));
    }

    public function testHtmlIsEscapedByDefault(): void
    {
        $renderer = $this->createRenderer();

        $renderer->init();

        $this->assertSame(
            '&lt;script&gt;alert(1)&lt;/script&gt;',
            $renderer->renderString('{{ value }}', ['value' => '<script>alert(1)</script>']),
        );
    }

    public function testFunctionCanBeAdded(): void
    {
        $renderer = $this->createRenderer();
        $renderer->init();

        $renderer->addFunction(new TwigFunction('shout', fn(string $s): string => strtoupper($s)));

        $this->assertSame('LOUD', $renderer->renderString("{{ shout('loud') }}"));
    }

    public function testFilterCanBeAdded(): void
    {
        $renderer = $this->createRenderer();
        $renderer->init();

        $renderer->addFilter(new TwigFilter('reverse', fn(string $s): string => strrev($s)));

        $this->assertSame('cba', $renderer->renderString("{{ 'abc'|reverse }}"));
    }

    public function testTestCanBeAdded(): void
    {
        $renderer = $this->createRenderer();
        $renderer->init();

        $renderer->addTest(new TwigTest('odd', fn(int $i): bool => $i % 2 !== 0));

        $this->assertSame('odd', $renderer->renderString("{{ 3 is odd ? 'odd' : 'even' }}"));
    }

    public function testGlobalCanBeAdded(): void
    {
        $renderer = $this->createRenderer();
        $renderer->init();

        $renderer->addGlobal('greeting', 'Hallo');

        $this->assertSame('Hallo', $renderer->renderString('{{ greeting }}'));
    }

    public function testTemplateFromTheThemeIsRendered(): void
    {
        $renderer = $this->createRenderer();

        $renderer->init();

        $this->assertSame("Hello\n", $renderer->renderTemplate('greeting.twig', ['name' => 'Welt']));
    }

    private function createRenderer(?EventManager $eventManager = null): TwigRenderer
    {
        $sitePath = dirname(__DIR__) . '/_data/twigrenderer';
        $config = new Config([
            'language' => 'de',
            'locale' => 'de_DE',
            'theme' => 'theme',
            'paths' => [
                'app' => dirname(__DIR__, 2),
                'pages' => $sitePath . '/pages',
                'plugins' => dirname(__DIR__, 2) . '/plugins',
                'site' => $sitePath,
                'themes' => $sitePath . '/themes',
            ],
            'fileExtensions' => ['layouts' => 'twig'],
        ]);

        $dataRepository = new class ($sitePath) implements DataRepositoryInterface {
            public function __construct(string $path) {}

            public function load(string $name): array
            {
                return [];
            }

            public function loadAll(): array
            {
                return [];
            }
        };

        $pageRepository = new class implements PageRepositoryInterface {
            public function find(string $id): ?Page
            {
                return null;
            }

            public function findAll(): PageList
            {
                return new PageList();
            }
        };

        $site = new Site($config, $dataRepository, $pageRepository, new UrlManager(new ServerRequest()));

        return new TwigRenderer($config, $eventManager ?? new EventManager(), new NullLogger(), $site);
    }
}
