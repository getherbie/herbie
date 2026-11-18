<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Config;
use herbie\DataRepositoryInterface;
use herbie\Page;
use herbie\PageList;
use herbie\PageRepositoryInterface;
use herbie\PageTrail;
use herbie\PageTree;
use herbie\Site;
use herbie\UrlManager;
use InvalidArgumentException;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;

final class SiteTest extends Unit
{
    private string $dataPath = '';

    protected function _after(): void
    {
        if ($this->dataPath !== '' && is_dir($this->dataPath)) {
            foreach (glob($this->dataPath . '/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($this->dataPath);
        }
    }

    public function testLanguageLocaleCharsetAndThemeComeFromTheConfig(): void
    {
        $site = $this->createSite(new Config([
            'language' => 'de',
            'locale' => 'de_DE',
            'charset' => 'UTF-8',
            'theme' => 'default',
        ]));

        $this->assertSame('de', $site->getLanguage());
        $this->assertSame('de_DE', $site->getLocale());
        $this->assertSame('UTF-8', $site->getCharset());
        $this->assertSame('default', $site->getTheme());
    }

    public function testMagicGetterResolvesFieldNames(): void
    {
        $site = $this->createSite(new Config(['language' => 'en', 'locale' => 'en_EN']));

        $this->assertSame('en', $site->language);
        $this->assertSame('en_EN', $site->locale);
    }

    public function testMagicGetterFindsGetBaseUrlForTheFieldNameBaseUrl(): void
    {
        $site = $this->createSite(new Config([]), [], [], '/');

        // "base_url" becomes getBaseurl(), and PHP method names are case insensitive
        $this->assertSame($site->getBaseUrl(), $site->base_url);
    }

    public function testMagicGetterThrowsForAnUnknownField(): void
    {
        $site = $this->createSite(new Config([]));

        $this->expectException(InvalidArgumentException::class);
        $site->does_not_exist;
    }

    public function testMagicIssetReturnsFalseForAnUnknownField(): void
    {
        $site = $this->createSite(new Config([]));

        $this->assertFalse(isset($site->does_not_exist));
    }

    public function testMagicIssetReturnsTrueForAKnownField(): void
    {
        $site = $this->createSite(new Config(['language' => 'de']));

        $this->assertTrue(isset($site->language));
    }

    public function testDataIsLoadedFromTheDataRepository(): void
    {
        $site = $this->createSite(new Config([]), ['menu' => ['a', 'b']]);

        // the repository keys the content by the file name
        $this->assertSame(['data' => ['menu' => ['a', 'b']]], $site->getData());
    }

    public function testPageListAndPageTreeComeFromTheRepository(): void
    {
        $pages = [
            'home' => $this->createPage('home', 'index.md', '', '2024-01-01T10:00:00+00:00'),
            'about' => $this->createPage('about', 'about.md', '', '2024-02-02T10:00:00+00:00'),
        ];
        $site = $this->createSite(new Config([]), [], $pages);

        $this->assertInstanceOf(PageList::class, $site->getPageList());
        $this->assertCount(2, $site->getPageList());
        $this->assertInstanceOf(PageTree::class, $site->getPageTree());
    }

    public function testModifiedReturnsTheLatestModificationDate(): void
    {
        $pages = [
            'home' => $this->createPage('home', 'index.md', '', '2024-01-01T10:00:00+00:00'),
            'about' => $this->createPage('about', 'about.md', '', '2024-02-02T10:00:00+00:00'),
        ];
        $site = $this->createSite(new Config([]), [], $pages);

        // compare the instant, the formatted string depends on the default time zone
        $this->assertSame(
            strtotime('2024-02-02T10:00:00+00:00'),
            strtotime($site->getModified()),
        );
    }

    public function testModifiedIsTheUnixEpochWithoutPages(): void
    {
        $site = $this->createSite(new Config([]));

        $this->assertSame(date('c', 0), $site->getModified());
    }

    public function testRouteAndParamsComeFromTheUrlManager(): void
    {
        $site = $this->createSite(new Config([]), [], [], '/about');

        $this->assertSame('about', $site->getRoute());
        $this->assertSame([], $site->getRouteParams());
    }

    public function testPageTrailIsReturnedForTheCurrentRoute(): void
    {
        $pages = [
            'about' => $this->createPage('about', 'about.md', '', '2024-01-01T10:00:00+00:00'),
        ];
        $site = $this->createSite(new Config([]), [], $pages, '/about');

        $this->assertInstanceOf(PageTrail::class, $site->getPageTrail());
    }

    public function testTimeIsAnIso8601Date(): void
    {
        $site = $this->createSite(new Config([]));

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $site->getTime(),
        );
    }

    private function createPage(string $route, string $filename, string $parentRoute, string $modified): Page
    {
        return new Page([
            'route' => $route,
            'path' => '/var/www/site/pages/' . $filename,
            'parent_route' => $parentRoute,
            'modified' => $modified,
        ], null);
    }

    private function createSite(
        Config $config,
        array $data = [],
        array $pages = [],
        string $path = '/',
    ): Site {
        $dataPath = sys_get_temp_dir() . '/herbie-site-test-' . bin2hex(random_bytes(4));
        $this->dataPath = $dataPath;
        mkdir($dataPath, 0777, true);
        if ($data !== []) {
            file_put_contents($dataPath . '/data.yml', \Symfony\Component\Yaml\Yaml::dump($data));
        }
        $dataRepository = new \herbie\YamlDataRepository($dataPath);

        $pageRepository = new class (new PageList($pages)) implements PageRepositoryInterface {
            public function __construct(private PageList $pageList) {}

            public function find(string $id): ?Page
            {
                return $this->pageList->getItem($id);
            }

            public function findAll(): PageList
            {
                return $this->pageList;
            }
        };

        $urlManager = new UrlManager((new ServerRequest())->withUri(new Uri('http://example.com' . $path)));

        return Site::fromServices($config, $dataRepository, $pageRepository, $urlManager);
    }
}
