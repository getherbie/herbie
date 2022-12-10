<?php

namespace tests\unit;

use Ausi\SlugGenerator\SlugGenerator;
use BadMethodCallException;
use herbie\Alias;
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

    private function initPage()
    {
        Page::setSlugGenerator(new SlugGenerator(['locale' => 'en', 'delimiter' => '-']));
    }

    // ---------------------------------------------------------
    // Tests for...
    // ---------------------------------------------------------
    // string[] $authors
    public function testAuthors()
    {
        Page::setSlugGenerator(new SlugGenerator());

        $page = new Page();
        $this->assertFalse($page->hasAuthor('None Existing'));
        $this->assertEquals('', $page->getAuthor('None Existing'));

        $page->setAuthor(' Jaco Pastorius ');
        $page->setAuthor("\n\nJames Jamerson\t\n\r");
        $this->assertTrue($page->hasAuthor('Jaco Pastorius'));
        $this->assertTrue($page->hasAuthor('James Jamerson'));
        $this->assertEquals('Jaco Pastorius', $page->getAuthor('Jaco Pastorius'));
        $this->assertEquals('James Jamerson', $page->getAuthor('James Jamerson'));

        $page->setAuthors(['Carol Kaye', 'Mark King']);
        $this->assertTrue($page->hasAuthor('Carol Kaye'));
        $this->assertTrue($page->hasAuthor('Mark King'));

        Page::unsetSlugGenerator();
    }

    // bool $cached
    public function testCached()
    {
        // default value
        $page = new Page();
        $this->assertEquals(true, $page->getCached());
        // setter/getter
        $page->setCached(false);
        $this->assertFalse($page->getCached());
        $this->assertFalse(!isset($page->cached));
        // magic setter/getter
        $page->cached = true;
        $this->assertTrue($page->cached);
        $this->assertTrue(isset($page->cached));
    }

    public function testCacheId()
    {
        // default value
        $page = new Page();
        $this->assertTrue(isset($page->cacheId));
        $this->assertEquals('page-', $page->cacheId);
        $page['id'] = 'path-to-page';
        $this->assertEquals('page-path-to-page', $page->cacheId);
        $page['cacheId'] = 'some-value'; // read-only
        $this->assertEquals('page-path-to-page', $page->cacheId); // still same value as before
    }

    // string[] $categories
    public function testCategories()
    {
        Page::setSlugGenerator(new SlugGenerator());

        $page = new Page();
        $this->assertFalse($page->hasCategory('None Existing Category'));
        $this->assertEquals('', $page->getCategory('None Existing Category'));

        $page->setCategory('Cool Jazz');
        $page->setCategory('Modern Jazz');
        $this->assertTrue($page->hasCategory('Cool Jazz'));
        $this->assertTrue($page->hasCategory('Modern Jazz'));
        $this->assertEquals('Cool Jazz', $page->getCategory('Cool Jazz'));
        $this->assertEquals('Modern Jazz', $page->getCategory('Modern Jazz'));

        $page->setCategories(['New-Orleans-Jazz', 'Soul-Jazz']);
        $this->assertTrue($page->hasCategory('New-Orleans-Jazz'));
        $this->assertTrue($page->hasCategory('Soul-Jazz'));

        Page::unsetSlugGenerator();
    }

    // string $content_type
    public function testContentType()
    {
        // default value
        $page = new Page();
        $this->assertEquals('text/html', $page->getContentType());
        // setter/getter
        $page->setContentType(" application/json \t"); // with whitespace
        $this->assertEquals('application/json', $page->getContentType());
        // magic setter/getter
        $page->content_type = "\n application/xml "; // with whitespace
        $this->assertEquals('application/xml', $page->content_type);
    }

    // string $created
    public function testCreated()
    {
        // default value
        $page = new Page();
        $this->assertEquals('', $page->getCreated());
        // setter/getter
        $page->setCreated("2022-11-22"); // with whitespace
        $this->assertEquals('2022-11-22T00:00:00+00:00', $page->getCreated());
        // magic setter/getter
        $page->created = "2022-10-20"; // with whitespace
        $this->assertEquals('2022-10-20T00:00:00+00:00', $page->created);
        // rest is tested in dateTest
    }

    // array<int|string, mixed> $customData
    // string $date
    public function testDate()
    {
        // default value
        $page = new Page();
        $this->assertEquals('', $page->getDate());
        // invalid date
        $page->setDate('invalid date');
        $this->assertEquals('', $page->getDate());
        $page->setDate(true);
        $this->assertEquals('', $page->getDate());
        $page->setDate(42.273424);
        $this->assertEquals('', $page->getDate());
        // setter/getter
        $page->setDate(" 2022-11-22 \t"); // with whitespace
        $this->assertEquals('2022-11-22T00:00:00+00:00', $page->getDate());
        // magic setter/getter
        $page->date = "\n 1667640550 "; // with whitespace
        $this->assertEquals('2022-11-05T09:29:10+00:00', $page->date);
        $page->date = 1667330550; // as integer
        $this->assertEquals('2022-11-01T19:22:30+00:00', $page->date);
    }

    // string $excerpt
    public function testExcert()
    {
        // default value
        $page = new Page();
        $this->assertEquals('', $page->getExcerpt());
        // setter/getter
        $page->setExcerpt(" This is an excerpt. \t"); // with whitespace
        $this->assertEquals('This is an excerpt.', $page->getExcerpt());
        // magic setter/getter
        $page->excerpt = "\n This is another excerpt. "; // with whitespace
        $this->assertEquals('This is another excerpt.', $page->excerpt);
    }

    // string $format
    public function testFormat()
    {
        // default value
        $page = new Page();
        $this->assertEquals('raw', $page->getFormat());
        // setter/getter
        $page->setFormat(" md \t"); // with whitespace
        $this->assertEquals('markdown', $page->getFormat());
        $page->setFormat("\n markdown "); // with whitespace
        $this->assertEquals('markdown', $page->getFormat());
        // magic setter/getter
        $page->format = 'textile';
        $this->assertEquals('textile', $page->format);
        $page->format = 'rst';
        $this->assertEquals('rest', $page->format);
        $page->format = 'raw';
        $this->assertEquals('raw', $page->format);
        $page->format = 'not-existin-format';
        $this->assertEquals('raw', $page->format);
    }

    // bool $hidden
    public function testHidden()
    {
        // default value
        $page = new Page();
        $this->assertFalse($page->getHidden());
        // setter/getter
        $page->setHidden(true);
        $this->assertTrue($page->getHidden());
        $this->assertTrue(isset($page->hidden));
        // magic setter/getter
        $page->hidden = false;
        $this->assertFalse($page->hidden);
        $this->assertFalse(!isset($page->hidden));
    }

    // bool $keep_extension
    public function testKeepExtension()
    {
        // default value
        $page = new Page();
        $this->assertFalse($page->getKeepExtension());
        // setter/getter
        $page->setKeepExtension(true);
        $this->assertTrue($page->getKeepExtension());
        $this->assertTrue(isset($page->keep_extension));
        // magic setter/getter
        $page->keep_extension = false;
        $this->assertFalse($page->keep_extension);
        $this->assertFalse(!isset($page->keep_extension));
    }

    // string $layout
    public function testLayout()
    {
        // default value
        $page = new Page();
        $this->assertEquals('default', $page->getLayout());
        // setter/getter
        $page->setLayout(" main \t"); // with whitespace
        $this->assertEquals('main', $page->getLayout());
        // magic setter/getter
        $page->layout = "\n default  "; // with whitespace
        $this->assertEquals('default', $page->layout);
    }

    // string $menu_title
    public function testMenuTitle()
    {
        // default value
        $page = new Page();
        $this->assertEquals('', $page->getMenuTitle());
        // if title set but menuTitle not
        $page->setTitle('My title');
        $this->assertEquals('My title', $page->getMenuTitle());
        // setter/getter
        $page->setMenuTitle(" Title \t"); // with whitespace
        $this->assertEquals('Title', $page->getMenuTitle());
        // magic setter/getter
        $page->menu_title = "\n New Title "; // with whitespace
        $this->assertEquals('New Title', $page->menu_title);
    }

    // string $modified
    public function testModified()
    {
        // default value
        $page = new Page();
        $this->assertEquals('', $page->getModified());
        // setter/getter
        $page->setModified("2022-11-22"); // with whitespace
        $this->assertEquals('2022-11-22T00:00:00+00:00', $page->getModified());
        // magic setter/getter
        $page->modified = "2022-10-20"; // with whitespace
        $this->assertEquals('2022-10-20T00:00:00+00:00', $page->modified);
        // rest is tested in dateTest
    }

    // string $path
    public function testPath()
    {
        // default value
        $page = new Page();
        $this->assertEquals('', $page->getPath());
        // setter/getter
        $page->setPath(" path/to/this \t"); // with whitespace
        $this->assertEquals('path/to/this', $page->getPath());
        // magic setter/getter
        $page->path = "\n path/to/that "; // with whitespace
        $this->assertEquals('path/to/that', $page->path);
    }

    // array<void>|array{status: int, url: string} $redirect
    public function testRedirect()
    {
        // default value
        $page = new Page();
        $this->assertEquals([], $page->getRedirect());
        // setter/getter
        $page->setRedirect(" redirect/to/page \t"); // with whitespace
        $this->assertEquals(['redirect/to/page', 302], $page->getRedirect());
        // magic setter/getter
        $page->redirect = ['redirect/to/another/page', 308];
        $this->assertEquals(['redirect/to/another/page', 308], $page->redirect);
        // invalid values with exceptions
        $invalidValues = [
            [42, 'Redirect must be a string or an array{string,int}.'],
            [true, 'Redirect must be a string or an array{string,int}.'],
            ['', 'Redirect must be a non-empty string.'],
            [[], 'Redirect must be a non-empty array.'],
            [['one-entry'], 'Redirect array must be an array{string,int}.'],
            [['/foo', 'no-integer'], 'Redirect array[1] must be a integer.'],
            [[false, 301], 'Redirect array[0] must be a string.'],
            [['', 300], 'Redirect array[0] must be a non-empty string.'],
            [['/foo', 299], 'Redirect array[1] must be a status code between 300 and 308.'],
            [['/bar', 309], 'Redirect array[1] must be a status code between 300 and 308.'],
        ];
        foreach ($invalidValues as $value) {
            try {
                $page->setRedirect($value[0]);
            } catch (InvalidArgumentException $e) {
                $message = 'Testing ' . json_encode($value[0]);
                $this->assertEquals($value[1], $e->getMessage(), $message);
            }
        }
    }

    // string $route
    public function testRoute()
    {
        // default value
        $page = new Page();
        $this->assertEquals('', $page->getRoute());
        // setter/getter
        $page->setRoute(" route/to/this \t"); // with whitespace
        $this->assertEquals('route/to/this', $page->getRoute());
        // magic setter/getter
        $page->route = "\n route/to/that "; // with whitespace
        $this->assertEquals('route/to/that', $page->route);
    }

    // string[] $tags
    public function testTags()
    {
        Page::setSlugGenerator(new SlugGenerator());

        $page = new Page();
        $this->assertFalse($page->hasTag('None Existing Tag'));
        $this->assertEquals('', $page->getTag('None Existing Tag'));

        $page->setTag('Calypso');
        $page->setTag('Son Cubano');
        $this->assertTrue($page->hasTag('Calypso'));
        $this->assertTrue($page->hasTag('Son Cubano'));
        $this->assertEquals('Calypso', $page->getTag('Calypso'));
        $this->assertEquals('Son Cubano', $page->getTag('Son Cubano'));

        $page->setTags(['Merengue', 'Salsa']);
        $this->assertTrue($page->hasTag('Merengue'));
        $this->assertTrue($page->hasTag('Salsa'));

        Page::unsetSlugGenerator();
    }

    // string $title
    public function testTitle()
    {
        // default value
        $page = new Page();
        $this->assertEquals('', $page->getTitle());
        // setter/getter
        $page->setTitle(" Title \t"); // with whitespace
        $this->assertEquals('Title', $page->getTitle());
        // magic setter/getter
        $page->title = "\n New Title "; // with whitespace
        $this->assertEquals('New Title', $page->title);
    }

    // bool $twig
    public function testTwig()
    {
        // default value
        $page = new Page();
        $this->assertTrue($page->getTwig());
        // setter/getter
        $page->setTwig(false);
        $this->assertFalse($page->getTwig());
        $this->assertFalse(!isset($page->twig));
        // magic setter/getter
        $page->twig = true;
        $this->assertTrue($page->twig);
        $this->assertTrue(isset($page->twig));
    }

    // string $type
    public function testType()
    {
        // default value
        $page = new Page();
        $this->assertEquals('page', $page->getType());
        // setter/getter
        $page->setType(" news \t"); // with whitespace
        $this->assertEquals('news', $page->getType());
        // magic setter/getter
        $page->type = "\n blog "; // with whitespace
        $this->assertEquals('blog', $page->type);
    }

    public function testToString()
    {
        $page = new Page();
        $this->assertEquals('', (string)$page);
        $page->title = 'Title';
        $this->assertEquals('Title', (string)$page);
    }

    public function testToArray()
    {
        $data = [
            // member data
            'authors' => [],
            'cached' => true,
            'categories' => [],
            'content_type' => 'text/html',
            'created' => '',
            'date' => '',
            'excerpt' => '',
            'format' => 'raw',
            'hidden' => false,
            'id' => '@page/index.md',
            'keep_extension' => false,
            'layout' => 'default',
            'menu_title' => '',
            'modified' => '',
            'parent_id' => '',
            'parent_route' => '',
            'path' => dirname(__DIR__) . '/_data/site/pages/herbie-info.html',
            'redirect' => ['test', 302],
            'route' => '',
            'tags' => [],
            'title' => '',
            'twig' => true,
            'type' => 'page',
            // custom data
            'aaa' => 'a',
            'bbb' => [],
            'ccc' => true,
            'ddd' => 42,
            'eee' => 23.375
        ];
        $page = new Page($data);
        $data['segments']['default'] = "\n{{ h_info() }}\n";
        $this->assertEquals($data, $page->toArray());
    }

    public function testMagicMethods()
    {
        $page = new Page(['title' => 'My Title']);
        $this->assertTrue(isset($page->title));
        $this->assertEquals('My Title', $page->title);
        $page->customTitle = 'Custom Title';
        $this->assertTrue(isset($page->customTitle));
        $this->assertEquals('Custom Title', $page->customTitle);
        $this->assertFalse(isset($page->nonExistingVariable));
        $this->expectExceptionMessage('Field nonExistingVariable does not exist.');
        (string)$page->nonExistingVariable;
    }

    public function testWithSlugGenerator()
    {
        $page = new Page(['author' => 'Niels-Henning Ørsted Pedersen']);
        try {
        } catch (BadMethodCallException $e) {
            $this->assertEquals('SlugGenerator not set.', $e->getMessage());
        }
        $this->assertNull(Page::setSlugGenerator(new SlugGenerator()));
        $this->assertEquals('Niels-Henning Ørsted Pedersen', $page->getAuthor('Niels-Henning Ørsted Pedersen'));
        $this->assertNull(Page::unsetSlugGenerator());
        try {
            $page->getAuthor('Niels-Henning Ørsted Pedersen');
        } catch (BadMethodCallException $e) {
            $this->assertEquals('SlugGenerator not set.', $e->getMessage());
        }
    }

    public function testSetDataWithDataKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field data is not allowed.');
        new Page(['data' => 'foo/bar']);
    }

    public function testArrayAccessMethods()
    {
        $page = new Page();
        $page['title'] = 'Title';
        $this->assertEquals('Title', $page['title']);
        $this->assertTrue(isset($page['title']));
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Unset is not supported.');
        unset($page['title']);
    }

    // ---------------------------------------------------------

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

    public function testToArray2()
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
     * @depends testToArray2
     */
    public function testMagicalGetMethod(Page $page)
    {
        // Member var
        $this->assertSame('layout.html', $page->layout);
        // User var
        $this->assertSame('This is a short text.', $page->excerpt);
    }

    /**
     * @depends testToArray2
     * @expectedException LogicException
     */
    public function testMagicalGetMethodException(Page $page)
    {
        $this->expectExceptionMessage("Field notExistingMember does not exist.");
        $page->notExistingMember;
    }

    /**
     * @depends testToArray2
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
     * @depends testToArray2
     */
    public function testToString2(Page $page)
    {
        $this->assertSame('Page Data', (string)$page);
    }
}
