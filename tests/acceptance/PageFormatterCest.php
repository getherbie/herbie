<?php

namespace Tests\Acceptance;

use AcceptanceTester;
use Codeception\Util\HttpCode;

class PageFormatterCest
{
    public function testMarkdownPageWithMdExtension(AcceptanceTester $I)
    {
        $I->amOnPage('/tests/formatter/markdown-1');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Markdown Page', 'h1');
        $I->see('This is a markdown formatted page.', 'p');
    }

    public function testMarkdownPageWithMarkdownExtension(AcceptanceTester $I)
    {
        $I->amOnPage('/tests/formatter/markdown-2');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Markdown Page', 'h1');
        $I->see('This is a markdown formatted page.', 'p');
    }

    public function testMarkdownPageWithTextExtension(AcceptanceTester $I)
    {
        $I->amOnPage('/tests/formatter/markdown-3');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Markdown Page', 'h1');
        $I->see('This is a markdown formatted page.', 'p');
    }
    
    public function testPageWithTextileExtension(AcceptanceTester $I)
    {
        $I->amOnPage('/tests/formatter/textile');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Textile Page', 'h1');
        $I->see('This is a textile formatted page.', 'p');
    }

    public function testPageWithHtmExtension(AcceptanceTester $I)
    {
        $I->amOnPage('/tests/formatter/htm');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('HTM Page', 'h1');
        $I->see('This is a HTM formatted page.', 'p');
    }

    public function testPageWithHtmlExtension(AcceptanceTester $I)
    {
        $I->amOnPage('/tests/formatter/html');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('HTML Page', 'h1');
        $I->see('This is a HTML formatted page.', 'p');
    }

    public function testPageWithTextExtension(AcceptanceTester $I)
    {
        $I->amOnPage('/tests/formatter/text');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Text Page');
        $I->see('This is a text formatted page.');
    }

    public function testPageWithRssExtension(AcceptanceTester $I)
    {
        $I->amOnPage('/tests/formatter/rss');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('RSS Page');
        $I->see('This is a RSS formatted page.');
    }
    
    public function testPageWithXmlExtension(AcceptanceTester $I)
    {
        $I->amOnPage('/tests/formatter/xml');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('XML Page');
        $I->see('This is a XML formatted page.');
    }

    public function testPageWithWrongExtension(AcceptanceTester $I)
    {
        $I->amOnPage('/tests/formatter/invalid');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->see('Page not found');
        $I->see('Oops, something got wrong!');
    }

    public function testTextPageWithVariousFormattings(AcceptanceTester $I)
    {
        $I->amOnPage('/tests/formatter/various');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Markdown Filter', 'h2');
        $I->see('Markdown Function', 'h2');
        $I->see('Textile Filter', 'h2');
        $I->see('Textile Function', 'h2');
    }
}
