<?php

namespace Tests\Unit;

use herbie\Alias;
use herbie\Config;
use herbie\FlatfilePagePersistence;
use herbie\FlatfilePageRepository;
use herbie\Page;
use herbie\PageFactory;
use InvalidArgumentException;
use LogicException;

class PageTest extends \Codeception\Test\Unit
{
    protected FlatfilePageRepository $repository;
    
    protected function _before()
    {
        $this->repository = new FlatfilePageRepository(
            new PageFactory(),
            new FlatfilePagePersistence(
                new Alias([
                    '@page' => __DIR__ . '/Fixtures/site/pages'
                ]),
                new Config([
                    'paths' => ['pages' => __DIR__ . '/Fixtures/site/pages'],
                    'fileExtensions' => ['pages' => 'md']
                ])
            )
        );
    }
    
    public function testConstructor()
    {
        $page = new Page();
        $layout = $page->getLayout();
        $this->assertSame('default', $layout);
        return $page;
    }

    public function testGetLayout()
    {
        $page = new Page();
        $this->assertSame('default', $page->getLayout());
    }

    public function testGetSegment()
    {
        $page = new Page();
        $page->setSegments([
            0 => 'Default Segment',
            1 => 'Segment 1',
            2 => 'Segment 2',
            'three' => 'Segment 3'
        ]);
        $this->assertSame('Default Segment', $page->getSegment(0));
        $this->assertSame('Segment 1', $page->getSegment(1));
        $this->assertSame('Segment 2', $page->getSegment(2));
        $this->assertSame('Segment 3', $page->getSegment('three'));
        $this->assertSame('', $page->getSegment('notExistingKey'));
    }

    public function testLoad()
    {
        $page = $this->repository->find('@page/segments.md');
        $this->assertSame('Segments', $page->getTitle());
        $this->assertSame('default', $page->getLayout());
        $this->assertSame('Default Segment', trim($page->getSegment('default')));
        $this->assertSame('Segment 1', trim($page->getSegment(1)));
        $this->assertSame('Segment 2', trim($page->getSegment(2)));
        $this->assertSame('Segment 3', trim($page->getSegment('three')));
        $this->assertSame('Invalid Segment', trim($page->getSegment(-1)));
        $this->assertSame('Last Segment', trim($page->getSegment('ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz_0123456789')));
    }

    public function testSetData()
    {
        $page = new Page(['title' => 'Testtitle', 'layout' => 'test']);
        $this->assertSame('Testtitle', $page->getTitle());
        $this->assertSame('test', $page->getLayout());
    }

    public function testSetDataException()
    {
        $this->expectException(InvalidArgumentException::class);
        new Page(['segments' => []]);
    }

    public function testSetDate()
    {
        date_default_timezone_set('Europe/Zurich');

        $page = new Page();

        $page->setDate('');
        $this->assertSame('', $page->getDate());

        $page->setDate(0);
        $this->assertSame('1970-01-01T01:00:00+01:00', $page->getDate());

        $page->setDate(time());
        $this->assertSame(date('c'), $page->getDate());

        $page->setDate('2013-12-24');
        $this->assertSame('2013-12-24', $page->getDate());
    }

    public function testToArray()
    {
        date_default_timezone_set('Europe/Zurich');

        $page = $this->repository->find('@page/pagedata.md');
        $page->authors = [];
        $array = [
            'id' => '@page/pagedata.md',
            'parent' => '',
            'segments' => ['default' => ''],
            'authors' => [],
            'cached' => 1,
            'categories' => [],
            'content_type' => 'text/html',
            'date' => '2013-12-24T01:00:00+01:00',
            'excerpt' => 'This is a short text.',
            'format' => 'markdown',
            'hidden' => 1,
            'keep_extension' => 0,
            'layout' => 'layout.html',
            'menu' => '',
            'modified' => '2022-09-10T08:57:53+02:00',
            'path' => '@page/pagedata.md',
            'redirect' => [],
            'route' => '',
            'tags' => [],
            'title' => 'My Title',
            'twig' => 1,
            'type' => 'my_type'
        ];
        $this->assertSame($array, $page->toArray());
        return $page;
    }

    /**
     * @depends testToArray
     */
    public function testMagicalGetMethod(Page $page)
    {
        // Member var
        $this->assertSame('layout.html', $page->layout);
        // User var
        $this->assertSame('This is a short text.', $page->excerpt);
    }

    /**
     * @depends testToArray
     * @expectedException LogicException
     */
    public function testMagicalGetMethodException(Page $page)
    {
        $this->expectExceptionMessage("Field notExistingMember does not exist.");
        $page->notExistingMember;
    }

    /**
     * @depends testToArray
     */
    public function testMagicalIssetMethod(Page $page)
    {
        // Member var
        $this->assertSame(true, isset($page->layout));
        // User var
        $this->assertSame(true, isset($page->excerpt));
        // Not existing member
        $this->assertSame(false, isset($page->notExistingMember));
    }

    /**
     * @depends testConstructor
     */
    public function testMagicalSetMethod(Page $page)
    {
        // Member var
        $page->title = 'My Title';
        // User var
        $page->uservar = 'My user var';
        $this->assertSame('My Title', $page->title);
        $this->assertSame('My user var', $page->uservar);
    }

    /**
     * @depends testToArray
     */
    public function testToString(Page $page)
    {
        $this->assertSame('My Title', strval($page));
    }
}
