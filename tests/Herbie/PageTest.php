<?php

class PageTest extends \PHPUnit\Framework\TestCase
{

    public function testConstructor()
    {
        $page = new Herbie\Page();
        $layout = $page->getLayout();
        $this->assertSame('default.html', $layout);
        return $page;
    }

    public function testGetLayout()
    {
        $page = new Herbie\Page();
        $this->assertSame('default.html', $page->getLayout());
        $this->assertSame('default', $page->getLayout(true));
    }

    public function testGetSegment()
    {
        $page = new Herbie\Page();
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
        $this->assertSame(NULL, $page->getSegment('notExistingKey'));
    }

    public function testLoad()
    {
        $path = TESTS_PATH . '/Fixtures/site/pages/segments.md';
        $parser = new \Symfony\Component\Yaml\Parser();
        $loader = new Herbie\Loader\PageLoader($path, $parser);

        $page = new Herbie\Page();
        $page->load($loader);

        $this->assertSame('Segments', $page->getTitle());
        $this->assertSame('default.html', $page->getLayout());
        $this->assertSame('Default Segment', trim($page->getSegment(0)));
        $this->assertSame('Segment 1', trim($page->getSegment(1)));
        $this->assertSame('Segment 2', trim($page->getSegment(2)));
        $this->assertSame("Segment 3\n\n--- -1 ---\n\nInvalid Segment", trim($page->getSegment('three')));
        $this->assertSame('Last Segment', trim($page->getSegment('ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz_0123456789')));
    }

    public function testSetData()
    {
        $page = new Herbie\Page();
        $page->setData(array('title' => 'Testtitle', 'layout' => 'test.html'));
        $this->assertSame('Testtitle', $page->getTitle());
        $this->assertSame('test.html', $page->getLayout());
    }

    /**
     * @expectedException LogicException
     */
    public function testSetDataException()
    {
        $page = new Herbie\Page();
        $page->setData(['segments' => []]);
    }

    public function testSetDate()
    {
        date_default_timezone_set('Europe/Zurich');

        $page = new Herbie\Page();

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

        $path = TESTS_PATH . '/Fixtures/site/pages/pagedata.md';
        $parser = new \Symfony\Component\Yaml\Parser();
        $loader = new Herbie\Loader\PageLoader($path, $parser);

        $page = new Herbie\Page();
        $page->load($loader);

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
    public function testMagicalGetMethod(Herbie\Page $page)
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
    public function testMagicalGetMethodException(Herbie\Page $page)
    {
        $page->notExistingMember;
    }

    /**
     * @depends testToArray
     */
    public function testMagicalIssetMethod(Herbie\Page $page)
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
    public function testMagicalSetMethod(Herbie\Page $page)
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
    public function testToString(Herbie\Page $page)
    {
        $this->assertSame('My Title', strval($page));
    }

}