<?php

namespace herbie\tests\unit;

use Ausi\SlugGenerator\SlugGenerator;
use Codeception\Test\Unit;
use herbie\Page;
use herbie\PageFactory;
use herbie\PageList;
use herbie\PageTree;

final class PageListTest extends Unit
{
    private PageFactory $pageFactory;

    public function testAddItemUsesRouteAsKey(): void
    {
        $list = new PageList();
        $list->addItem(new Page(['route' => 'about']));
        $list->addItem(new Page(['route' => 'contact']));

        $this->assertSame(2, $list->count());
        $this->assertNotNull($list->getItem('about'));
        $this->assertNotNull($list->getItem('contact'));
        $this->assertNull($list->getItem('not-existing'));
    }

    public function testAddItemOverwritesSameRoute(): void
    {
        $list = new PageList();
        $list->addItem(new Page(['route' => 'about', 'title' => 'First']));
        $list->addItem(new Page(['route' => 'about', 'title' => 'Second']));

        $this->assertSame(1, $list->count());
        $this->assertSame('Second', $list->getItem('about')->getTitle());
    }

    public function testIteration(): void
    {
        $list = $this->createList([
            ['route' => 'a', 'title' => 'A'],
            ['route' => 'b', 'title' => 'B'],
        ]);

        $titles = [];
        foreach ($list as $page) {
            $titles[] = $page->getTitle();
        }
        $this->assertSame(['A', 'B'], $titles);
    }

    public function testFilterByCallback(): void
    {
        $list = $this->createList([
            ['route' => 'a', 'title' => 'Alpha'],
            ['route' => 'b', 'title' => 'Beta'],
        ]);

        $filtered = $list->filter(fn(Page $page): bool => $page->getTitle() === 'Beta');

        $this->assertSame(1, $filtered->count());
        $this->assertNotNull($filtered->getItem('b'));
        $this->assertNull($filtered->getItem('a'));
    }

    public function testFilterByKeyAndValue(): void
    {
        $list = $this->createList([
            ['route' => 'a', 'layout' => 'default'],
            ['route' => 'b', 'layout' => 'wide'],
        ]);

        $filtered = $list->filter('layout', 'wide');

        $this->assertSame(1, $filtered->count());
        $this->assertSame('b', $filtered->getItem('b')->getRoute());
    }

    public function testFilterWithoutArgumentsKeepsAllPages(): void
    {
        $list = $this->createList([
            ['route' => 'a'],
            ['route' => 'b'],
        ]);

        // an object is always true, so filtering by truthiness keeps every page
        $this->assertSame(2, $list->filter()->count());
    }

    public function testSortByField(): void
    {
        $list = $this->createList([
            ['route' => 'c', 'title' => 'Charlie'],
            ['route' => 'a', 'title' => 'Alpha'],
            ['route' => 'b', 'title' => 'Beta'],
        ]);

        $titles = [];
        foreach ($list->sort('title') as $page) {
            $titles[] = $page->getTitle();
        }
        $this->assertSame(['Alpha', 'Beta', 'Charlie'], $titles);
    }

    public function testSortDescending(): void
    {
        $list = $this->createList([
            ['route' => 'a', 'title' => 'Alpha'],
            ['route' => 'c', 'title' => 'Charlie'],
        ]);

        $titles = [];
        foreach ($list->sort('title', 'desc') as $page) {
            $titles[] = $page->getTitle();
        }
        $this->assertSame(['Charlie', 'Alpha'], $titles);
    }

    public function testSortDefaultFieldIsTitle(): void
    {
        $list = $this->createList([
            ['route' => 'b', 'title' => 'Beta'],
            ['route' => 'a', 'title' => 'Alpha'],
        ]);

        $titles = [];
        foreach ($list->sort() as $page) {
            $titles[] = $page->getTitle();
        }
        $this->assertSame(['Alpha', 'Beta'], $titles);
    }

    public function testSortByCallback(): void
    {
        $list = $this->createList([
            ['route' => 'a', 'title' => 'Alpha'],
            ['route' => 'b', 'title' => 'Beta'],
        ]);

        $sorted = $list->sort(fn(Page $a, Page $b): int => strcmp($b->getTitle(), $a->getTitle()));

        $titles = [];
        foreach ($sorted as $page) {
            $titles[] = $page->getTitle();
        }
        $this->assertSame(['Beta', 'Alpha'], $titles);
    }

    public function testShuffleKeepsAllItems(): void
    {
        $list = $this->createList([
            ['route' => 'a'],
            ['route' => 'b'],
            ['route' => 'c'],
        ]);

        $shuffled = $list->shuffle();

        // the route is preserved, so the pages are still reachable by their key
        $this->assertSame(3, $shuffled->count());
        $this->assertSame('a', $shuffled->getItem('a')->getRoute());
        $this->assertSame('b', $shuffled->getItem('b')->getRoute());
        $this->assertSame('c', $shuffled->getItem('c')->getRoute());
    }

    public function testGetTags(): void
    {
        $list = $this->createListWithSlug([
            ['route' => 'a', 'tags' => ['php', 'cms'], 'type' => 'page'],
            ['route' => 'b', 'tags' => ['php'], 'type' => 'post'],
        ]);

        $this->assertSame(['cms' => 1, 'php' => 2], $list->getTags());
        $this->assertSame(['php' => 1], $list->getTags('post'));
        $this->assertSame([], $list->getTags('not-existing'));
    }

    public function testGetCategories(): void
    {
        $list = $this->createListWithSlug([
            ['route' => 'a', 'categories' => ['News', 'Tech']],
            ['route' => 'b', 'categories' => ['News']],
        ]);

        $this->assertSame(['News' => 2, 'Tech' => 1], $list->getCategories());
    }

    public function testGetAuthors(): void
    {
        $list = $this->createListWithSlug([
            ['route' => 'a', 'authors' => ['Ada']],
            ['route' => 'b', 'authors' => ['Ada', 'Bob']],
        ]);

        $this->assertSame(['Ada' => 2, 'Bob' => 1], $list->getAuthors());
    }

    public function testGetYears(): void
    {
        $list = $this->createList([
            ['route' => 'a', 'date' => '2022-11-22'],
            ['route' => 'b', 'date' => '2022-05-01'],
            ['route' => 'c', 'date' => '2021-01-01'],
        ]);

        $this->assertSame(['2022' => 2, '2021' => 1], $list->getYears());
    }

    public function testGetMonths(): void
    {
        $list = $this->createList([
            ['route' => 'a', 'date' => '2022-11-22'],
            ['route' => 'b', 'date' => '2022-11-01'],
            ['route' => 'c', 'date' => '2022-05-01'],
        ]);

        $months = $list->getMonths();

        $this->assertArrayHasKey('2022-11', $months);
        $this->assertSame(2, $months['2022-11']['count']);
        $this->assertArrayHasKey('2022-05', $months);
        // months are sorted in descending order
        $this->assertSame('2022-11', array_key_first($months));
    }

    public function testGetPageTree(): void
    {
        $list = $this->createList([
            ['route' => '', 'path' => '@page/index.md', 'title' => 'Index'],
            ['route' => 'about', 'path' => '@page/about/index.md', 'title' => 'About'],
            ['route' => 'about/team', 'path' => '@page/about/team.md', 'title' => 'Team'],
        ]);

        $tree = $list->getPageTree();

        $this->assertInstanceOf(PageTree::class, $tree);
        $this->assertNotNull($tree->findByRoute('about'));
        $this->assertNotNull($tree->findByRoute('about/team'));
        $this->assertNull($tree->findByRoute('not-existing'));
    }

    public function testGetPageTreeIsCached(): void
    {
        $list = $this->createList([['route' => '', 'path' => '@page/index.md']]);

        $this->assertSame($list->getPageTree(), $list->getPageTree());
    }

    public function testGetPageTrail(): void
    {
        $list = $this->createList([
            ['route' => '', 'path' => '@page/index.md', 'title' => 'Index'],
            ['route' => 'about', 'path' => '@page/about/index.md', 'title' => 'About'],
            ['route' => 'about/team', 'path' => '@page/about/team.md', 'title' => 'Team'],
        ]);

        $trail = $list->getPageTrail('about/team');

        // the start page is not part of the trail, it is added by the breadcrumb function
        $this->assertSame(2, $trail->count());
        $titles = [];
        foreach ($trail as $page) {
            $titles[] = $page->getTitle();
        }
        $this->assertSame(['About', 'Team'], $titles);
    }

    public function testGetPageTrailSkipsMissingRoutes(): void
    {
        $list = $this->createList([
            ['route' => '', 'path' => '@page/index.md', 'title' => 'Index'],
            ['route' => 'a/b/c', 'path' => '@page/a/b/c.md', 'title' => 'Deep'],
        ]);

        $trail = $list->getPageTrail('a/b/c');

        // only the page that exists is part of the trail
        $this->assertSame(1, $trail->count());
    }

    public function testQuery(): void
    {
        $list = $this->createList([
            ['route' => 'a', 'title' => 'Alpha'],
            ['route' => 'b', 'title' => 'Beta'],
        ]);

        $result = $list->query()->where('title=Beta')->all();

        $this->assertCount(1, $result);
    }

    /**
     * @param array<int, array<string, mixed>> $data
     */
    private function createList(array $data): PageList
    {
        $list = new PageList();
        foreach ($data as $item) {
            $list->addItem($this->pageFactory->newPageItem($item));
        }
        return $list;
    }

    /**
     * @param array<int, array<string, mixed>> $data
     */
    private function createListWithSlug(array $data): PageList
    {
        $list = new PageList();
        foreach ($data as $item) {
            $list->addItem(new Page($item, null, new SlugGenerator()));
        }
        return $list;
    }

    protected function _before(): void
    {
        $this->pageFactory = new PageFactory();
    }
}
