<?php

namespace Tests\Unit;

use herbie\Alias;
use herbie\Config;
use herbie\FlatfilePagePersistence;
use herbie\FlatfilePageRepository;
use herbie\Page;
use herbie\PageFactory;
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
        $page->setSegments(array(
            0 => 'Default Segment',
            1 => 'Segment 1',
            2 => 'Segment 2',
            'three' => 'Segment 3'
        ));
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
        $this->assertSame('Default Segment', trim($page->getSegment(0)));
        $this->assertSame('Segment 1', trim($page->getSegment(1)));
        $this->assertSame('Segment 2', trim($page->getSegment(2)));
        $this->assertSame("Segment 3\n\n--- -1 ---\n\nInvalid Segment", trim($page->getSegment('three')));
        $this->assertSame('Last Segment', trim($page->getSegment('ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz_0123456789')));
    }

    public function testSetData()
    {
        $page = new Page(array('title' => 'Testtitle', 'layout' => 'test.html'));
        $this->assertSame('Testtitle', $page->getTitle());
        $this->assertSame('test.html', $page->getLayout());
    }

    /**
     * @expectedException LogicException
     */
    public function testSetDataException()
    {
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

        $array = [
            'layout' => 'layout.html',
            'type' => 'my_type',
            'title' => 'My Title',
            # Yaml wandelt 2013-12-24 in einen Unix-Timestamp um
            # Herbie wiederum wandelt einen Timestamp in ein ISO8601 Datum um.
            'date' => '2013-12-24T00:00:00+01:00',
            'abstract' => 'This is a short text.',
            'keywords' => 'Keyword 1, Keyword 2, Keyword 3'
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
        $this->assertSame('This is a short text.', $page->abstract);
    }

    /**
     * @depends testToArray
     * @expectedException LogicException
     * @expectedExceptionMessage Field notExistingMember does not exist.
     */
    public function testMagicalGetMethodException(Page $page)
    {
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
        $this->assertSame(true, isset($page->abstract));
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
