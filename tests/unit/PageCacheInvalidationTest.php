<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Alias;
use herbie\Cache;
use herbie\Config;
use herbie\EventManager;
use herbie\FileCache;
use herbie\Finder;
use herbie\FlatFilePagePersistence;
use herbie\FlatFilePageRepository;
use herbie\middlewares\PageRendererMiddleware;
use herbie\middlewares\PageResolverMiddleware;
use herbie\Page;
use herbie\PageFactory;
use herbie\plugins\search\SearchPlugin;
use herbie\UrlManager;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class PageCacheInvalidationTest extends Unit
{
    private string $tempDir;
    private string $pagesDir;
    private string $pageFile;
    private Cache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/herbie-cache-' . bin2hex(random_bytes(6));
        $this->pagesDir = $this->tempDir . '/pages';
        $this->pageFile = $this->pagesDir . '/about.md';
        mkdir($this->pagesDir, 0755, true);
        $this->cache = new Cache(new FileCache($this->tempDir . '/cache'));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    public function testPageListCacheIsInvalidatedByFileMtime(): void
    {
        $persistence = $this->createPersistence();
        $this->writePage('Old page list content', 1700000000);

        $first = $persistence->findAll();
        $this->assertStringContainsString('Old page list content', $first[0]['segments']['default']);
        $this->assertSame(1700000000, $this->cache->get('herbie_pages_modified'));

        $this->writePage('New page list content', 1700000001);

        $second = $persistence->findAll();
        $this->assertStringContainsString('New page list content', $second[0]['segments']['default']);
        $this->assertSame(1700000001, $this->cache->get('herbie_pages_modified'));
        $this->assertFalse($this->cache->has('pagelist_' . md5('herbie\FlatFilePagePersistence::findAll') . '_1700000000'));
    }

    public function testRenderedPageCacheIsInvalidatedByFileMtime(): void
    {
        $this->writePage('Old rendered content', 1700000000);
        $middleware = $this->createMiddleware();

        $page = $this->loadPage();
        $first = (string)$middleware->process($this->requestFor($page), $this->terminalHandler())->getBody();
        $this->assertStringContainsString('Old rendered content', $first);

        $this->writePage('New rendered content', 1700000001);

        $page = $this->loadPage();
        $second = (string)$middleware->process($this->requestFor($page), $this->terminalHandler())->getBody();
        $this->assertStringContainsString('New rendered content', $second);
        $this->assertFalse($this->cache->has('page_' . md5($page->getCacheId()) . '_1700000000'));
    }

    public function testSearchCacheIsInvalidatedByFileMtime(): void
    {
        $this->writePage('Old needle content', 1700000000);
        $plugin = $this->createPluginWithFreshRepository();
        $this->invokePrivate($plugin, 'search', ['needle', false]);
        $oldResults = $this->invokePrivate($plugin, 'search', ['needle', false]);
        $this->assertStringContainsString('Old needle content', strip_tags($oldResults[0]['snippet']));

        $this->writePage('New needle content', 1700000001);
        $plugin = $this->createPluginWithFreshRepository();
        $newResults = $this->invokePrivate($plugin, 'search', ['needle', false]);
        $this->assertStringContainsString('New needle content', strip_tags($newResults[0]['snippet']));
        $this->assertFalse($this->cache->has('search_' . md5('@page/about.md') . '_1700000000'));
    }

    private function writePage(string $content, int $mtime): void
    {
        file_put_contents($this->pageFile, "---\ntitle: About\nlayout: ''\n---\n" . $content . "\n");
        touch($this->pageFile, $mtime, $mtime);
        clearstatcache(true);
    }

    private function createPersistence(): FlatFilePagePersistence
    {
        return new FlatFilePagePersistence(
            new Alias(['@page' => $this->pagesDir]),
            $this->cache,
            new Finder(['path' => $this->pagesDir, 'extensions' => ['md']]),
            ['cache' => true, 'cacheTTL' => 3600],
        );
    }

    private function loadPage(): Page
    {
        $data = $this->createPersistence()->findAll()[0];
        return (new PageFactory())->newPage($data['data'], $data['segments']);
    }

    private function createMiddleware(): PageRendererMiddleware
    {
        return new PageRendererMiddleware(
            $this->cache,
            new EventManager(),
            new ResponseFactory(),
            new UrlManager(new ServerRequest(), ['niceUrls' => false]),
            ['cache' => true, 'cacheTTL' => 3600],
        );
    }

    private function createPluginWithFreshRepository(): SearchPlugin
    {
        $repository = new FlatFilePageRepository(new PageFactory(), $this->createPersistence());
        $repository->findAll(); // the page list is loaded before the search in a real request

        $plugin = (new ReflectionClass(SearchPlugin::class))->newInstanceWithoutConstructor();
        $this->setProperty($plugin, 'cache', $this->cache);
        $this->setProperty($plugin, 'config', new Config(['plugins' => ['search' => ['useCache' => true]]]));
        $this->setProperty($plugin, 'eventManager', new EventManager());
        $this->setProperty($plugin, 'pageRepository', $repository);
        $this->setProperty($plugin, 'urlManager', new UrlManager(new ServerRequest(), ['niceUrls' => false]));
        return $plugin;
    }

    private function setProperty(SearchPlugin $plugin, string $name, mixed $value): void
    {
        (new ReflectionProperty(SearchPlugin::class, $name))->setValue($plugin, $value);
    }

    private function invokePrivate(object $object, string $method, array $arguments): mixed
    {
        return (new ReflectionMethod($object, $method))->invokeArgs($object, $arguments);
    }

    private function requestFor(Page $page): ServerRequestInterface
    {
        return (new ServerRequest())
            ->withUri(new Uri('http://example.com/'))
            ->withAttribute(PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_PAGE, $page)
            ->withAttribute(PageResolverMiddleware::HERBIE_REQUEST_ATTRIBUTE_ROUTE, '');
    }

    private function terminalHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw new \LogicException('The terminal handler must not be called.');
            }
        };
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $directory . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($directory);
    }
}
