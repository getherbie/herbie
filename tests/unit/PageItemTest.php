<?php

namespace tests\unit;

use Ausi\SlugGenerator\SlugGenerator;
use herbie\PageItem;

final class PageItemTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
    }

    private function initPage()
    {
        PageItem::setSlugGenerator(new SlugGenerator(['locale' => 'en', 'delimiter' => '-']));
    }

    // ---------------------------------------------------------
    // Tests for...
    // ---------------------------------------------------------
    // string[] $authors

    // bool $cached
    public function testCached()
    {
        // default value
        $pageItem = new PageItem();
        $this->assertEquals(true, $pageItem->getCached());
        // setter/getter
        $pageItem->setCached(false);
        $this->assertFalse($pageItem->getCached());
        $this->assertFalse(!isset($pageItem->cached));
        // magic setter/getter
        $pageItem->cached = true;
        $this->assertTrue($pageItem->cached);
        $this->assertTrue(isset($pageItem->cached));
    }

    // string[] $categories

    // string $content_type
    public function testContentType()
    {
        // default value
        $pageItem = new PageItem();
        $this->assertEquals('text/html', $pageItem->getContentType());
        // setter/getter
        $pageItem->setContentType(" application/json \t"); // with whitespace
        $this->assertEquals('application/json', $pageItem->getContentType());
        // magic setter/getter
        $pageItem->content_type = "\n application/xml "; // with whitespace
        $this->assertEquals('application/xml', $pageItem->content_type);
    }

    // string $created
    public function testCreated()
    {
        // default value
        $pageItem = new PageItem();
        $this->assertEquals('', $pageItem->getCreated());
        // setter/getter
        $pageItem->setCreated("2022-11-22"); // with whitespace
        $this->assertEquals('2022-11-22T00:00:00+00:00', $pageItem->getCreated());
        // magic setter/getter
        $pageItem->created = "2022-10-20"; // with whitespace
        $this->assertEquals('2022-10-20T00:00:00+00:00', $pageItem->created);
        // rest is tested in dateTest
    }

    // array<int|string, mixed> $customData
    // string $date
    public function testDate()
    {
        // default value
        $pageItem = new PageItem();
        $this->assertEquals('', $pageItem->getDate());
        // invalid date
        $pageItem->setDate('invalid date');
        $this->assertEquals('', $pageItem->getDate());
        $pageItem->setDate(true);
        $this->assertEquals('', $pageItem->getDate());
        $pageItem->setDate(42.273424);
        $this->assertEquals('', $pageItem->getDate());
        // setter/getter
        $pageItem->setDate(" 2022-11-22 \t"); // with whitespace
        $this->assertEquals('2022-11-22T00:00:00+00:00', $pageItem->getDate());
        // magic setter/getter
        $pageItem->date = "\n 1667640550 "; // with whitespace
        $this->assertEquals('2022-11-05T09:29:10+00:00', $pageItem->date);
        $pageItem->date = 1667330550; // as integer
        $this->assertEquals('2022-11-01T19:22:30+00:00', $pageItem->date);
    }

    // string $excerpt
    public function testExcert()
    {
        // default value
        $pageItem = new PageItem();
        $this->assertEquals('', $pageItem->getExcerpt());
        // setter/getter
        $pageItem->setExcerpt(" This is an excerpt. \t"); // with whitespace
        $this->assertEquals('This is an excerpt.', $pageItem->getExcerpt());
        // magic setter/getter
        $pageItem->excerpt = "\n This is another excerpt. "; // with whitespace
        $this->assertEquals('This is another excerpt.', $pageItem->excerpt);
    }

    // string $format
    public function testFormat()
    {
        // default value
        $pageItem = new PageItem();
        $this->assertEquals('raw', $pageItem->getFormat());
        // setter/getter
        $pageItem->setFormat(" md \t"); // with whitespace
        $this->assertEquals('markdown', $pageItem->getFormat());
        $pageItem->setFormat("\n markdown "); // with whitespace
        $this->assertEquals('markdown', $pageItem->getFormat());
        // magic setter/getter
        $pageItem->format = 'textile';
        $this->assertEquals('textile', $pageItem->format);
        $pageItem->format = 'rst';
        $this->assertEquals('rest', $pageItem->format);
        $pageItem->format = 'raw';
        $this->assertEquals('raw', $pageItem->format);
        $pageItem->format = 'not-existin-format';
        $this->assertEquals('raw', $pageItem->format);
    }

    // bool $hidden
    public function testHidden()
    {
        // default value
        $pageItem = new PageItem();
        $this->assertFalse($pageItem->getHidden());
        // setter/getter
        $pageItem->setHidden(true);
        $this->assertTrue($pageItem->getHidden());
        $this->assertTrue(isset($pageItem->hidden));
        // magic setter/getter
        $pageItem->hidden = false;
        $this->assertFalse($pageItem->hidden);
        $this->assertFalse(!isset($pageItem->hidden));
    }

    // bool $keep_extension
    public function testKeepExtension()
    {
        // default value
        $pageItem = new PageItem();
        $this->assertFalse($pageItem->getKeepExtension());
        // setter/getter
        $pageItem->setKeepExtension(true);
        $this->assertTrue($pageItem->getKeepExtension());
        $this->assertTrue(isset($pageItem->keep_extension));
        // magic setter/getter
        $pageItem->keep_extension = false;
        $this->assertFalse($pageItem->keep_extension);
        $this->assertFalse(!isset($pageItem->keep_extension));
    }

    // string $layout
    public function testLayout()
    {
        // default value
        $pageItem = new PageItem();
        $this->assertEquals('default', $pageItem->getLayout());
        // setter/getter
        $pageItem->setLayout(" main \t"); // with whitespace
        $this->assertEquals('main', $pageItem->getLayout());
        // magic setter/getter
        $pageItem->layout = "\n default  "; // with whitespace
        $this->assertEquals('default', $pageItem->layout);
    }

    // string $menu_title
    public function testMenuTitle()
    {
        // default value
        $pageItem = new PageItem();
        $this->assertEquals('', $pageItem->getMenuTitle());
        // if title set but menuTitle not
        $pageItem->setTitle('My title');
        $this->assertEquals('My title', $pageItem->getMenuTitle());
        // setter/getter
        $pageItem->setMenuTitle(" Title \t"); // with whitespace
        $this->assertEquals('Title', $pageItem->getMenuTitle());
        // magic setter/getter
        $pageItem->menu_title = "\n New Title "; // with whitespace
        $this->assertEquals('New Title', $pageItem->menu_title);
    }

    // string $modified
    public function testModified()
    {
        // default value
        $pageItem = new PageItem();
        $this->assertEquals('', $pageItem->getModified());
        // setter/getter
        $pageItem->setModified("2022-11-22"); // with whitespace
        $this->assertEquals('2022-11-22T00:00:00+00:00', $pageItem->getModified());
        // magic setter/getter
        $pageItem->modified = "2022-10-20"; // with whitespace
        $this->assertEquals('2022-10-20T00:00:00+00:00', $pageItem->modified);
        // rest is tested in dateTest
    }

    // string $path
    public function testPath()
    {
        // default value
        $pageItem = new PageItem();
        $this->assertEquals('', $pageItem->getPath());
        // setter/getter
        $pageItem->setPath(" path/to/this \t"); // with whitespace
        $this->assertEquals('path/to/this', $pageItem->getPath());
        // magic setter/getter
        $pageItem->path = "\n path/to/that "; // with whitespace
        $this->assertEquals('path/to/that', $pageItem->path);
    }

    // array<void>|array{status: int, url: string} $redirect
    public function testRedirect()
    {
        // default value
        $pageItem = new PageItem();
        $this->assertEquals([], $pageItem->getRedirect());
        // setter/getter
        $pageItem->setRedirect(" redirect/to/page \t"); // with whitespace
        $this->assertEquals(['redirect/to/page', 302], $pageItem->getRedirect());
        // magic setter/getter
        $pageItem->redirect = ['redirect/to/another/page', 308];
        $this->assertEquals(['redirect/to/another/page', 308], $pageItem->redirect);
        // invalid values with exceptions
        $invalidValues = [
            [42, 'Redirect must be a string or an array{string,int}.'],
            ['', 'Redirect URL must be a non-empty string.'],
            [['one-entry'], 'Redirect array must be an array{string,int}.'],
            [[false, 301], 'Redirect array[0] must be a string.'],
            [['/foo', 299], 'Redirect array[1] must be a status code between 300 and 308.'],
            [['/bar', 309], 'Redirect array[1] must be a status code between 300 and 308.'],
            [['', 300], 'Redirect URL must be a non-empty string.'],
        ];
        foreach ($invalidValues as $value) {
            try {
                $pageItem->setRedirect($value[0]);
            } catch (\InvalidArgumentException $e) {
                $message = 'Testing ' . json_encode($value[0]);
                $this->assertEquals($value[1], $e->getMessage(), $message);
            }
        }
    }

    // string $route
    public function testRoute()
    {
        // default value
        $pageItem = new PageItem();
        $this->assertEquals('', $pageItem->getRoute());
        // setter/getter
        $pageItem->setRoute(" route/to/this \t"); // with whitespace
        $this->assertEquals('route/to/this', $pageItem->getRoute());
        // magic setter/getter
        $pageItem->route = "\n route/to/that "; // with whitespace
        $this->assertEquals('route/to/that', $pageItem->route);
    }

    // string[] $tags

    // string $title
    public function testTitle()
    {
        // default value
        $pageItem = new PageItem();
        $this->assertEquals('', $pageItem->getTitle());
        // setter/getter
        $pageItem->setTitle(" Title \t"); // with whitespace
        $this->assertEquals('Title', $pageItem->getTitle());
        // magic setter/getter
        $pageItem->title = "\n New Title "; // with whitespace
        $this->assertEquals('New Title', $pageItem->title);
    }

    // bool $twig
    public function testTwig()
    {
        // default value
        $pageItem = new PageItem();
        $this->assertTrue($pageItem->getTwig());
        // setter/getter
        $pageItem->setTwig(false);
        $this->assertFalse($pageItem->getTwig());
        $this->assertFalse(!isset($pageItem->twig));
        // magic setter/getter
        $pageItem->twig = true;
        $this->assertTrue($pageItem->twig);
        $this->assertTrue(isset($pageItem->twig));
    }

    // string $type
    public function testType()
    {
        // default value
        $pageItem = new PageItem();
        $this->assertEquals('page', $pageItem->getType());
        // setter/getter
        $pageItem->setType(" news \t"); // with whitespace
        $this->assertEquals('news', $pageItem->getType());
        // magic setter/getter
        $pageItem->type = "\n blog "; // with whitespace
        $this->assertEquals('blog', $pageItem->type);
    }

    // ---------------------------------------------------------
}
