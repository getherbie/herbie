<?php

declare(strict_types=1);

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Page;
use herbie\PageTree;
use herbie\plugins\twig\PageTreeFilterIterator;
use herbie\plugins\twig\PageTreeHtmlRenderer;
use herbie\plugins\twig\PageTreeIterator;
use herbie\plugins\twig\PageTreeTextRenderer;

/**
 * The renderers turn a page tree into a nested list or an ASCII tree. They are used by the
 * Twig functions menu_tree and menu_ascii_tree.
 */
final class PageTreeRendererTest extends Unit
{
    public function testIteratorExposesTheChildrenOfABranch(): void
    {
        $iterator = new PageTreeIterator($this->createTree());

        $titles = [];
        foreach ($iterator as $node) {
            $titles[] = $node->getMenuItem()->getTitle();
        }

        // the iterator itself is flat, the renderers make it recursive
        $this->assertSame(['Home', 'About'], $titles);
    }

    public function testIteratorReportsChildrenOnlyWhereTheNodeHasSome(): void
    {
        $iterator = new PageTreeIterator($this->createTree());

        $this->assertFalse($iterator->hasChildren());
        $iterator->next();
        $this->assertTrue($iterator->hasChildren());
    }

    public function testIteratorProvidesTheChildrenOfTheCurrentNode(): void
    {
        $iterator = new PageTreeIterator($this->createTree());

        // move to "About", the only node with children
        $iterator->next();
        $children = $iterator->getChildren();

        $this->assertTrue($children->valid());
        $this->assertSame('Team', $children->getMenuItem()->getTitle());
    }

    public function testFilterRemovesHiddenPages(): void
    {
        $iterator = new PageTreeIterator($this->createTree());
        $filter = new PageTreeFilterIterator($iterator, true);

        $titles = [];
        foreach ($filter as $node) {
            $titles[] = $node->getMenuItem()->getTitle();
        }

        $this->assertSame(['Home', 'About'], $titles);
    }

    public function testFilterKeepsHiddenPagesWhenDisabled(): void
    {
        $iterator = new PageTreeIterator($this->createTree());
        $filter = new PageTreeFilterIterator($iterator, false);

        $titles = [];
        foreach ($filter as $node) {
            $titles[] = $node->getMenuItem()->getTitle();
        }

        $this->assertSame(['Home', 'About'], $titles);
    }

    public function testFilterAppliesToNestedChildren(): void
    {
        $iterator = new PageTreeIterator($this->createTree());
        $filter = new PageTreeFilterIterator($iterator, true);

        $titles = [];
        foreach (new \RecursiveIteratorIterator($filter, \RecursiveIteratorIterator::SELF_FIRST) as $node) {
            $titles[] = $node->getMenuItem()->getTitle();
        }

        $this->assertSame(['Home', 'About', 'Team'], $titles);
    }

    public function testHtmlRendererCreatesANestedList(): void
    {
        $renderer = $this->createHtmlRenderer('menu');

        $html = $renderer->render();

        $this->assertStringStartsWith('<div class="menu"><ul>', $html);
        $this->assertStringEndsWith('</ul></div>', $html);
        $this->assertStringContainsString('<li>Home</li>', $html);
        $this->assertStringContainsString('<li>About', $html);
        $this->assertStringContainsString('<li>Team</li>', $html);
    }

    public function testHtmlRendererUsesTheGivenClass(): void
    {
        $renderer = $this->createHtmlRenderer('sidebar');

        $this->assertStringContainsString('<div class="sidebar"><ul>', $renderer->render());
    }

    public function testHtmlRendererMarksTheCurrentPage(): void
    {
        $renderer = $this->createHtmlRenderer('');

        $html = $renderer->render('about');

        $this->assertStringContainsString('<li class="current active">About', $html);
        $this->assertStringNotContainsString('<li class="current active">Home', $html);
    }

    public function testHtmlRendererMarksAParentAsActive(): void
    {
        $renderer = $this->createHtmlRenderer('');

        $html = $renderer->render('about/team');

        // the parent is not the current page, but it is part of the trail
        $this->assertStringContainsString('<li class="active">About', $html);
        $this->assertStringContainsString('<li class="current active">Team', $html);
    }

    public function testHtmlRendererUsesTheTitleWhenNoCallbackIsSet(): void
    {
        $renderer = $this->createHtmlRenderer('');

        $html = $renderer->render();

        $this->assertStringContainsString('<li>Home</li>', $html);
    }

    public function testHtmlRendererTemplateCanBeOverridden(): void
    {
        $renderer = $this->createHtmlRenderer('');
        $renderer->setTemplate([
            'beginIteration' => '<nav>',
            'endIteration' => '</nav>',
        ]);

        $html = $renderer->render();

        $this->assertStringStartsWith('<nav>', $html);
        $this->assertStringEndsWith('</nav>', $html);
    }

    public function testTextRendererCreatesAnAsciiTree(): void
    {
        $iterator = new PageTreeIterator($this->createTree());
        $renderer = new PageTreeTextRenderer(new PageTreeFilterIterator($iterator, true));

        $output = $renderer->render();

        $this->assertStringContainsString('Home', $output);
        $this->assertStringContainsString('About', $output);
        $this->assertStringContainsString('Team', $output);
        $this->assertStringContainsString(PHP_EOL, $output);
    }

    public function testTextRendererUsesTheEmptyTitleForPagesWithoutOne(): void
    {
        $tree = new PageTree();
        $tree->addChild(new PageTree($this->createPage('home', 'Home', '/', 'Home')));
        $tree->addChild(new PageTree($this->createPage('untitled', '', '/untitled', '')));

        $renderer = new PageTreeTextRenderer(new PageTreeFilterIterator(new PageTreeIterator($tree), true));
        $renderer->emptyTitle = '---';

        $this->assertStringContainsString('---', $renderer->render());
    }

    private function createHtmlRenderer(string $class): PageTreeHtmlRenderer
    {
        $iterator = new PageTreeIterator($this->createTree());
        $renderer = new PageTreeHtmlRenderer(new PageTreeFilterIterator($iterator, true));
        $renderer->setClass($class);
        $renderer->setItemCallback(fn(PageTree $node): string => $node->getMenuItem()->getTitle());

        return $renderer;
    }

    private function createTree(): PageTree
    {
        $tree = new PageTree();

        $home = new PageTree($this->createPage('home', 'Home', '/', 'Home'));
        $about = new PageTree($this->createPage('about', 'About', '/about', 'About'));
        $about->addChild(new PageTree($this->createPage('about/team', 'Team', '/about/team', 'Team')));

        $draft = $this->createPage('draft', 'Draft', '/about/draft', 'Draft');
        $draft->setHidden(true);
        $about->addChild(new PageTree($draft));

        $tree->addChild($home);
        $tree->addChild($about);

        return $tree;
    }

    private function createPage(string $route, string $title, string $path, string $menuTitle): Page
    {
        return new Page([
            'route' => $route,
            'title' => $title,
            'menu_title' => $menuTitle,
            'path' => '/var/www/site/pages' . $path . '.md',
        ], null);
    }
}
