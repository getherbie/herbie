<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Ausi\SlugGenerator\SlugGenerator;
use Codeception\Test\Unit;
use herbie\Page;
use herbie\PageFactory;
use herbie\PageList;
use herbie\PageTrail;
use herbie\PageTree;

final class PageFactoryTest extends Unit
{
    public function testNewPageCreatesAPageWithSegments(): void
    {
        $factory = new PageFactory();

        $page = $factory->newPage(['title' => 'About'], ['a' => 'content']);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame('About', $page->getTitle());
        $this->assertSame(['a' => 'content'], $page->getSegments());
    }

    public function testNewPageItemCreatesAPageWithoutSegments(): void
    {
        $factory = new PageFactory();

        $page = $factory->newPageItem(['title' => 'About']);

        $this->assertInstanceOf(Page::class, $page);
        $this->assertSame('About', $page->getTitle());
    }

    public function testNewPageListIsEmptyByDefault(): void
    {
        $factory = new PageFactory();

        $pageList = $factory->newPageList();

        $this->assertInstanceOf(PageList::class, $pageList);
        $this->assertCount(0, $pageList);
    }

    public function testNewPageTreeBuildsTheHierarchy(): void
    {
        $factory = new PageFactory();

        $tree = $factory->newPageTree($this->createPageList());

        // the root node is the container for the whole tree and has no page of its own
        $root = $tree->findByRoute('');
        $this->assertInstanceOf(PageTree::class, $root);
        $this->assertNull($root->getMenuItem());

        $home = $tree->findByRoute('home');
        $this->assertInstanceOf(PageTree::class, $home);
        $this->assertSame('Home', (string)$home);

        $about = $tree->findByRoute('about');
        $this->assertInstanceOf(PageTree::class, $about);
        $this->assertSame('About', (string)$about);

        $team = $tree->findByRoute('about/team');
        $this->assertInstanceOf(PageTree::class, $team);
        $this->assertSame('Team', (string)$team);
    }

    public function testNewPageTreeReturnsNullForAnUnknownRoute(): void
    {
        $factory = new PageFactory();

        $tree = $factory->newPageTree($this->createPageList());

        $this->assertNull($tree->findByRoute('does/not/exist'));
    }

    public function testNewPageTreeAttachesIndexPagesForTheSameParentInOrder(): void
    {
        $factory = new PageFactory();
        $pageList = new PageList([
            $this->createPage('about', 'index.md', '', 'About'),
            $this->createPage('team', 'team.md', 'about', 'Team'),
            $this->createPage('vision', 'vision.md', 'about', 'Vision'),
        ]);

        $tree = $factory->newPageTree($pageList);
        $about = $tree->findByRoute('about');

        $this->assertInstanceOf(PageTree::class, $about);
        $children = [];
        foreach ($about as $child) {
            $this->assertInstanceOf(PageTree::class, $child);
            $children[] = (string)$child;
        }
        $this->assertSame(['Team', 'Vision'], $children);
    }

    public function testNewPageTreeFollowsThePageListOrderForIndexPages(): void
    {
        $factory = new PageFactory();
        $pageList = new PageList([
            $this->createPage('about', '1-about/index.md', '', 'About'),
            $this->createPage('about/team', '1-about/1-team.md', 'about', 'Team'),
            $this->createPage('about/projects', '1-about/2-projects/index.md', 'about', 'Projects'),
            $this->createPage('about/vision', '1-about/3-vision.md', 'about', 'Vision'),
        ]);

        $tree = $factory->newPageTree($pageList);
        $about = $tree->findByRoute('about');

        $this->assertInstanceOf(PageTree::class, $about);
        $children = [];
        foreach ($about as $child) {
            $this->assertInstanceOf(PageTree::class, $child);
            $children[] = (string)$child;
        }
        $this->assertSame(['Team', 'Projects', 'Vision'], $children);
    }

    public function testNewPageTreeAttachesChildrenListedBeforeTheirParent(): void
    {
        $factory = new PageFactory();
        $pageList = new PageList([
            $this->createPage('about/team', '1-about/1-team.md', 'about', 'Team'),
            $this->createPage('about', '1-about/index.md', '', 'About'),
        ]);

        $tree = $factory->newPageTree($pageList);

        $team = $tree->findByRoute('about/team');
        $this->assertInstanceOf(PageTree::class, $team);
        $this->assertSame('Team', (string)$team);
    }

    public function testNewPageTreeAlsoFindsNestedIndexPages(): void
    {
        $factory = new PageFactory();
        $pageList = new PageList([
            $this->createPage('home', 'index.md', '', 'Home'),
            $this->createPage('about', '1-about/index.md', '', 'About'),
            $this->createPage('about/team', '1-about/2-team/index.md', 'about', 'Team'),
        ]);

        $tree = $factory->newPageTree($pageList);

        $team = $tree->findByRoute('about/team');
        $this->assertInstanceOf(PageTree::class, $team);
        $this->assertSame('Team', (string)$team);
    }

    public function testNewPageTreeIgnoresPagesWithoutADefinedParent(): void
    {
        $factory = new PageFactory();
        $pageList = new PageList([
            $this->createPage('orphan', 'orphan.md', 'no/such/parent', 'Orphan'),
        ]);

        $tree = $factory->newPageTree($pageList);

        $this->assertNull($tree->findByRoute('orphan'));
    }

    public function testNewPageTreeFindByUsesAPageProperty(): void
    {
        $factory = new PageFactory();

        $tree = $factory->newPageTree($this->createPageList());

        $node = $tree->findBy('route', 'about');
        $this->assertInstanceOf(PageTree::class, $node);
        $this->assertSame('About', (string)$node);

        $this->assertFalse($tree->findBy('route', 'unknown'));
    }

    public function testNewPageTrailIsCountableAndIterable(): void
    {
        $factory = new PageFactory();
        $pages = [$factory->newPageItem(['title' => 'A']), $factory->newPageItem(['title' => 'B'])];

        $trail = $factory->newPageTrail($pages);

        $this->assertInstanceOf(PageTrail::class, $trail);
        $this->assertCount(2, $trail);

        $titles = [];
        foreach ($trail as $page) {
            $titles[] = $page->getTitle();
        }
        $this->assertSame(['A', 'B'], $titles);
    }

    public function testNewPageTrailCanBeEmpty(): void
    {
        $factory = new PageFactory();

        $trail = $factory->newPageTrail([]);

        $this->assertCount(0, $trail);
    }

    public function testSlugGeneratorIsPassedToThePage(): void
    {
        $factory = new PageFactory(new SlugGenerator(['validChars' => 'a-zA-Z0-9']));

        $page = $factory->newPageItem([
            'authors' => ['Jaco Pastorius'],
            'categories' => ['Live Music'],
        ]);

        // both sides of the comparison run through the generator, so the spaced form matches
        $this->assertTrue($page->hasAuthor('Jaco Pastorius'));
        $this->assertTrue($page->hasCategory('Live Music'));
        $this->assertFalse($page->hasAuthor('Someone Else'));
    }

    public function testPageWithoutSlugGeneratorThrowsWhenAuthorsAreCompared(): void
    {
        $factory = new PageFactory();
        $page = $factory->newPageItem(['authors' => ['Jaco Pastorius']]);

        $this->expectException(\BadMethodCallException::class);
        $page->hasAuthor('Jaco Pastorius');
    }

    private function createPageList(): PageList
    {
        return new PageList([
            $this->createPage('home', 'index.md', '', 'Home'),
            $this->createPage('about', '1-about/index.md', '', 'About'),
            $this->createPage('about/team', '1-about/1-team.md', 'about', 'Team'),
        ]);
    }

    private function createPage(string $route, string $filename, string $parentRoute, string $title): Page
    {
        return new Page([
            'route' => $route,
            'path' => '/var/www/site/pages/' . $filename,
            'parent_route' => $parentRoute,
            'title' => $title,
        ], null);
    }
}
