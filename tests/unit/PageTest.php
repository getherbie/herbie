<?php

namespace herbie\tests\unit;

use Ausi\SlugGenerator\SlugGenerator;
use BadMethodCallException;
use Codeception\Test\Unit;
use herbie\Page;
use herbie\PageFactory;
use InvalidArgumentException;

use function herbie\date_format;

final class PageTest extends Unit
{
    public function testAuthors()
    {
        $page = new Page([], null, new SlugGenerator());
        $this->assertFalse($page->hasAuthor('None Existing'));
        $this->assertEquals('', $page->getAuthor('None Existing'));

        $page = new Page(['authors' => ['Jaco Pastorius', 'James Jamerson']], null, new SlugGenerator());
        $this->assertTrue($page->hasAuthor('Jaco Pastorius'));
        $this->assertTrue($page->hasAuthor('James Jamerson'));
        $this->assertEquals('Jaco Pastorius', $page->getAuthor('Jaco Pastorius'));
        $this->assertEquals('James Jamerson', $page->getAuthor('James Jamerson'));
        $this->assertSame(['Jaco Pastorius', 'James Jamerson'], $page->authors);

        $page = new Page(['authors' => ['Carol Kaye', 'Mark King']], null, new SlugGenerator());
        $this->assertTrue($page->hasAuthor('Carol Kaye'));
        $this->assertTrue($page->hasAuthor('Mark King'));
    }

    public function testCached()
    {
        // default value
        $page = new Page();
        $this->assertTrue($page->getCached());
        $this->assertTrue(isset($page->cached));
        // set via page properties
        $page = new Page(['cached' => false]);
        $this->assertFalse($page->getCached());
        $this->assertFalse($page->cached);
    }

    public function testCacheId()
    {
        // default value
        $page = new Page();
        $this->assertEquals('page-', $page->getCacheId());
        // derives from the id
        $page = new Page(['id' => 'path-to-page']);
        $this->assertEquals('page-path-to-page', $page->getCacheId());
        $this->assertEquals('page-path-to-page', $page->cacheId);
    }

    public function testCategories()
    {
        $page = new Page([], null, new SlugGenerator());
        $this->assertFalse($page->hasCategory('None Existing Category'));
        $this->assertEquals('', $page->getCategory('None Existing Category'));

        $page = new Page(['categories' => ['Cool Jazz', 'Modern Jazz']], null, new SlugGenerator());
        $this->assertTrue($page->hasCategory('Cool Jazz'));
        $this->assertTrue($page->hasCategory('Modern Jazz'));
        $this->assertEquals('Cool Jazz', $page->getCategory('Cool Jazz'));
        $this->assertEquals('Modern Jazz', $page->getCategory('Modern Jazz'));

        $page = new Page(['categories' => ['New-Orleans-Jazz', 'Soul-Jazz']], null, new SlugGenerator());
        $this->assertTrue($page->hasCategory('New-Orleans-Jazz'));
        $this->assertTrue($page->hasCategory('Soul-Jazz'));
    }

    public function testContentType()
    {
        // default value
        $page = new Page();
        $this->assertEquals('text/html', $page->getContentType());
        // set via page properties
        $page = new Page(['content_type' => 'application/json']);
        $this->assertEquals('application/json', $page->getContentType());
        $this->assertEquals('application/json', $page->content_type);
    }

    public function testCreated()
    {
        // default value
        $page = new Page();
        $this->assertEquals('', $page->getCreated());
        // parsed from page properties
        $page = new Page(['created' => '2022-11-22']);
        $this->assertEquals('2022-11-22T00:00:00+00:00', $page->getCreated());
        $this->assertEquals('2022-11-22T00:00:00+00:00', $page->created);
        // rest is tested in testDate
    }

    public function testDate()
    {
        // default value
        $page = new Page();
        $this->assertEquals('', $page->getDate());
        // invalid date
        $this->assertEquals('', (new Page(['date' => 'invalid date']))->getDate());
        $this->assertEquals('', (new Page(['date' => true]))->getDate());
        $this->assertEquals('', (new Page(['date' => 42.273424]))->getDate());
        // parsed from page properties
        $this->assertEquals(
            '2022-11-22T00:00:00+00:00',
            (new Page(['date' => '2022-11-22']))->getDate()
        );
        // as string timestamp
        $this->assertEquals(
            '2022-11-05T09:29:10+00:00',
            (new Page(['date' => '1667640550']))->getDate()
        );
        // as integer timestamp
        $this->assertEquals(
            '2022-11-01T19:22:30+00:00',
            (new Page(['date' => 1667330550]))->getDate()
        );
    }

    public function testSetDate()
    {
        $timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Zurich');

        try {
            $this->assertSame('', (new Page(['date' => '']))->getDate());
            $this->assertSame('1970-01-01T01:00:00+01:00', (new Page(['date' => 0]))->getDate());
            $this->assertSame(date_format('c'), (new Page(['date' => time()]))->getDate());
            $this->assertSame('2013-12-24T00:00:00+01:00', (new Page(['date' => '2013-12-24']))->getDate());
        } finally {
            date_default_timezone_set($timezone);
        }
    }

    public function testExcerpt()
    {
        // default value
        $page = new Page();
        $this->assertEquals('', $page->getExcerpt());
        // set via page properties
        $page = new Page(['excerpt' => 'This is an excerpt.']);
        $this->assertEquals('This is an excerpt.', $page->getExcerpt());
        $this->assertEquals('This is an excerpt.', $page->excerpt);
    }

    public function testFormat()
    {
        // default value
        $page = new Page();
        $this->assertEquals('raw', $page->getFormat());
        // mapped to the internal name
        $this->assertEquals('markdown', (new Page(['format' => 'md']))->format);
        $this->assertEquals('markdown', (new Page(['format' => 'markdown']))->format);
        $this->assertEquals('textile', (new Page(['format' => 'textile']))->format);
        $this->assertEquals('rest', (new Page(['format' => 'rst']))->format);
        $this->assertEquals('raw', (new Page(['format' => 'raw']))->format);
        $this->assertEquals('raw', (new Page(['format' => 'not-existin-format']))->format);
    }

    public function testHidden()
    {
        // default value
        $page = new Page();
        $this->assertFalse($page->getHidden());
        // set via page properties
        $page = new Page(['hidden' => true]);
        $this->assertTrue($page->getHidden());
        $this->assertTrue(isset($page->hidden));
    }

    public function testKeepExtension()
    {
        // default value
        $page = new Page();
        $this->assertFalse($page->getKeepExtension());
        // set via page properties
        $page = new Page(['keep_extension' => true]);
        $this->assertTrue($page->getKeepExtension());
        $this->assertTrue(isset($page->keep_extension));
    }

    public function testLayout()
    {
        // default value
        $page = new Page();
        $this->assertEquals('default', $page->getLayout());
        // set via page properties
        $page = new Page(['layout' => 'main']);
        $this->assertEquals('main', $page->getLayout());
        $this->assertEquals('main', $page->layout);
    }

    public function testMenuTitle()
    {
        // default value
        $page = new Page();
        $this->assertEquals('', $page->getMenuTitle());
        // if title set but menu title not, the title is used
        $page = new Page(['title' => 'My title']);
        $this->assertEquals('My title', $page->getMenuTitle());
        // set via page properties
        $page = new Page(['title' => 'My title', 'menu_title' => 'My menu title']);
        $this->assertEquals('My menu title', $page->getMenuTitle());
        $this->assertEquals('My menu title', $page->menu_title);
    }

    public function testModified()
    {
        // default value
        $page = new Page();
        $this->assertEquals('', $page->getModified());
        // parsed from page properties
        $page = new Page(['modified' => '2022-11-22']);
        $this->assertEquals('2022-11-22T00:00:00+00:00', $page->getModified());
        $this->assertEquals('2022-11-22T00:00:00+00:00', $page->modified);
    }

    public function testPath()
    {
        // default value
        $page = new Page();
        $this->assertEquals('', $page->getPath());
        // set via page properties
        $page = new Page(['path' => 'path/to/this']);
        $this->assertEquals('path/to/this', $page->getPath());
        $this->assertEquals('path/to/this', $page->path);
    }

    public function testRedirect()
    {
        // default value
        $page = new Page();
        $this->assertEquals([], $page->getRedirect());
        // a string becomes an array with status code 302
        $page = new Page(['redirect' => 'redirect/to/page']);
        $this->assertEquals(['redirect/to/page', 302], $page->getRedirect());
        // an array is kept
        $page = new Page(['redirect' => ['redirect/to/another/page', 308]]);
        $this->assertEquals(['redirect/to/another/page', 308], $page->redirect);
        // invalid values with exceptions
        $invalidValues = [
            [42, 'Redirect must be a string or an array{string,int}.'],
            [true, 'Redirect must be a string or an array{string,int}.'],
            ['', 'Redirect must be a non-empty string.'],
            [[], 'Redirect array must be an array{string,int}.'],
            [['one-entry'], 'Redirect array must be an array{string,int}.'],
            [['/foo', 'no-integer'], 'Redirect array[1] must be a integer.'],
            [[false, 301], 'Redirect array[0] must be a non-empty string.'],
            [['', 300], 'Redirect array[0] must be a non-empty string.'],
            [['/foo', 299], 'Redirect array[1] must be a status code between 300 and 308.'],
            [['/bar', 309], 'Redirect array[1] must be a status code between 300 and 308.'],
        ];
        foreach ($invalidValues as $value) {
            try {
                new Page(['redirect' => $value[0]]);
            } catch (InvalidArgumentException $e) {
                $message = 'Testing ' . json_encode($value[0]);
                $this->assertEquals($value[1], $e->getMessage(), $message);
            }
        }
    }

    public function testRoute()
    {
        // default value
        $page = new Page();
        $this->assertEquals('', $page->getRoute());
        $this->assertTrue($page->isStartPage());
        // set via page properties
        $page = new Page(['route' => 'route/to/this']);
        $this->assertEquals('route/to/this', $page->getRoute());
        $this->assertEquals('route/to/this', $page->route);
        $this->assertFalse($page->isStartPage());
        $this->assertTrue($page->routeEquals('route/to/this'));
        $this->assertTrue($page->routeInPageTrail('route/to/this/subpage'));
        $this->assertFalse($page->routeInPageTrail('other/route'));
    }

    public function testTags()
    {
        $page = new Page([], null, new SlugGenerator());
        $this->assertFalse($page->hasTag('None Existing Tag'));
        $this->assertEquals('', $page->getTag('None Existing Tag'));

        $page = new Page(['tags' => ['Calypso', 'Son Cubano']], null, new SlugGenerator());
        $this->assertTrue($page->hasTag('Calypso'));
        $this->assertTrue($page->hasTag('Son Cubano'));
        $this->assertEquals('Calypso', $page->getTag('Calypso'));
        $this->assertEquals('Son Cubano', $page->getTag('Son Cubano'));

        $page = new Page(['tags' => ['Merengue', 'Salsa']], null, new SlugGenerator());
        $this->assertTrue($page->hasTag('Merengue'));
        $this->assertTrue($page->hasTag('Salsa'));
    }

    public function testTitle()
    {
        // default value
        $page = new Page();
        $this->assertEquals('', $page->getTitle());
        // set via page properties
        $page = new Page(['title' => 'Title']);
        $this->assertEquals('Title', $page->getTitle());
        $this->assertEquals('Title', $page->title);
        // the title is used for the string representation
        $this->assertEquals('Title', (string)$page);
    }

    public function testToString()
    {
        $page = new Page();
        $this->assertEquals('', (string)$page);
        $page = new Page(['title' => 'Title']);
        $this->assertEquals('Title', (string)$page);
    }

    public function testTwig()
    {
        // default value
        $page = new Page();
        $this->assertTrue($page->getTwig());
        // set via page properties
        $page = new Page(['twig' => false]);
        $this->assertFalse($page->getTwig());
        $this->assertFalse($page->twig);
        $this->assertTrue(isset($page->twig));
    }

    public function testType()
    {
        // default value
        $page = new Page();
        $this->assertEquals('page', $page->getType());
        // set via page properties
        $page = new Page(['type' => 'news']);
        $this->assertEquals('news', $page->getType());
        $this->assertEquals('news', $page->type);
    }

    public function testCustomData()
    {
        $page = new Page(['unknown' => 'value', 'other' => 42]);
        $this->assertEquals('value', $page->unknown);
        $this->assertEquals(42, $page->other);
        $this->assertEquals(['unknown' => 'value', 'other' => 42], $page->getData());
        $this->assertTrue(isset($page->unknown));
        $this->assertFalse(isset($page->notExisting));
        $array = $page->toArray();
        $this->assertEquals('value', $array['unknown']);
        $this->assertEquals(42, $array['other']);
    }

    public function testUnknownPropertyThrows()
    {
        $page = new Page();
        $this->expectException(InvalidArgumentException::class);
        $page->notExisting;
    }

    public function testDataFieldIsNotAllowed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Page(['data' => []]);
    }

    public function testSegmentsFieldIsNotAllowed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Page(['segments' => []]);
    }

    public function testSegments()
    {
        $page = new Page([], ['default' => "--- default ---\nfoo", 'sidebar' => 'bar']);
        $this->assertEquals('bar', $page->getSegment('sidebar'));
        $this->assertEquals('', $page->getSegment('not-existing'));
        $this->assertSame(['default' => "--- default ---\nfoo", 'sidebar' => 'bar'], $page->getSegments());
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
        $this->assertSame('Segment 1', trim($page->getSegment('1')));
        $this->assertSame('Segment 2', trim($page->getSegment('2')));
        $this->assertSame('Segment 3', trim($page->getSegment('three')));
        $this->assertSame('Invalid Segment', trim($page->getSegment('-1')));
        $this->assertSame(
            'Last Segment',
            trim($page->getSegment('ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz_0123456789'))
        );
    }

    public function testSetData()
    {
        $page = new Page(['title' => 'Test title', 'layout' => 'test']);
        $this->assertSame('Test title', $page->getTitle());
        $this->assertSame('test', $page->getLayout());
    }

    public function testArrayAccess()
    {
        $page = new Page(['title' => 'Title']);
        $this->assertTrue(isset($page['title']));
        $this->assertEquals('Title', $page['title']);
        $page['title'] = 'New Title';
        $this->assertEquals('New Title', $page->title);
        $this->assertFalse(isset($page['notExisting']));
    }

    public function testArrayAccessUnsetIsNotSupported()
    {
        $page = new Page();
        $this->expectException(BadMethodCallException::class);
        unset($page['title']);
    }

    public function testToArray()
    {
        $page = new Page([
            'title' => 'Title',
            'layout' => 'main',
            'format' => 'md',
            'date' => '2022-11-22',
            'custom' => 'value',
        ]);
        $array = $page->toArray();
        $this->assertEquals('Title', $array['title']);
        $this->assertEquals('main', $array['layout']);
        $this->assertEquals('markdown', $array['format']);
        $this->assertEquals('2022-11-22T00:00:00+00:00', $array['date']);
        $this->assertEquals('value', $array['custom']);
        $this->assertArrayNotHasKey('slugGenerator', $array);
        $this->assertArrayNotHasKey('customData', $array);
    }

    public function testToArrayContainsAllProperties(): void
    {
        $array = (new Page())->toArray();
        foreach (['authors', 'cached', 'categories', 'content_type', 'created', 'date', 'excerpt',
                  'format', 'hidden', 'id', 'keep_extension', 'layout', 'menu_title', 'modified',
                  'parent_id', 'parent_route', 'path', 'redirect', 'route', 'segments', 'tags',
                  'title', 'twig', 'type'] as $key) {
            $this->assertArrayHasKey($key, $array);
        }
    }

    public function testWithSlugGenerator(): void
    {
        $page = new Page(['tags' => ['Cool Jazz']], null, new SlugGenerator());
        $this->assertTrue($page->hasTag('Cool Jazz'));
    }

    public function testWithoutSlugGeneratorThrows(): void
    {
        $page = new Page();
        $this->expectException(BadMethodCallException::class);
        $page->hasTag('anything');
    }
}
