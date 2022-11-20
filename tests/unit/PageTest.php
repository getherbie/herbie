<?php

namespace tests\unit;

use herbie\Alias;
use herbie\Config;
use herbie\FlatFileIterator;
use herbie\FlatFilePagePersistence;
use herbie\FlatFilePageRepository;
use herbie\NullCache;
use herbie\Page;
use herbie\PageFactory;
use InvalidArgumentException;
use LogicException;

use function herbie\date_format;

final class PageTest extends \Codeception\Test\Unit
{
    protected FlatFilePageRepository $repository;

    protected function _before()
    {
        $this->repository = new FlatFilePageRepository(
            new PageFactory(),
            new FlatFilePagePersistence(
                new Alias([
                    '@page' => __DIR__ . '/Fixtures/site/pages'
                ]),
                new NullCache(),
                new FlatFileIterator(
                    dirname(__DIR__) . '/integration/Fixtures/site/pages',
                    ['md']
                )
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
        $page = (new PageFactory())->newPage(
            [
                'title' => 'Segments'
            ],
            [
                'default' => 'Default Segment',
                '1' => 'Segment 1',
                '2' => 'Segment 2',
                'three' => 'Segment 3',
                '-1' => 'Invalid Segment',
                'ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz_0123456789' => 'Last Segment',
            ]
        );
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
        $page = new Page(['title' => 'Test title', 'layout' => 'test']);
        $this->assertSame('Test title', $page->getTitle());
        $this->assertSame('test', $page->getLayout());
    }

    public function testSetDataException()
    {
        $this->expectException(InvalidArgumentException::class);
        new Page(['data' => []]);
    }

    public function testSetSegmentsException()
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
        $this->assertSame(date_format('c'), $page->getDate());

        $page->setDate('2013-12-24');
        $this->assertSame('2013-12-24T00:00:00+01:00', $page->getDate());
    }

    public function testToArray()
    {
        $data = [
            'parent' => '',
            'authors' => [],
            'cached' => true,
            'categories' => [],
            'content_type' => 'text/html',
            'created' => '',
            'date' => '2013-12-24T01:00:00+01:00',
            'excerpt' => 'This is a short text.',
            'format' => 'markdown',
            'hidden' => true,
            'id' => '@page/pagedata.md',
            'keep_extension' => false,
            'layout' => 'layout.html',
            'menu_title' => '',
            'modified' => '2022-09-13T04:43:13+02:00',
            'parent_id' => '@page/index.md',
            'parent_route' => '',
            'path' => '@page/pagedata.md',
            'redirect' => ['test', 301],
            'route' => 'pagedata',
            'tags' => [],
            'title' => 'Page Data',
            'twig' => true,
            'type' => 'my_type',
            'a' => 'A',
            'b' => 2,
            'c' => true,
            'd' => ['one', 'two'],
            'e' => 5.74
        ];

        $expected = array_merge($data, ['segments' => []]);
        $expected['menu_title'] = $expected['title'];

        $page = (new PageFactory())->newPage($data, []);
        $this->assertEquals($expected, $page->toArray());

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
        $this->assertSame('Page Data', (string)$page);
    }
}
