<?php

namespace herbie\tests\acceptance;

use AcceptanceTester;
use Codeception\Util\HttpCode;

final class PageFormatterCest
{
    public function testMarkdownPageWithMdExtension(AcceptanceTester $I): void
    {
        $I->amOnPage('/formatter/markdown-1');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Markdown Page', 'h1');
        $I->see('This is a markdown formatted page.', 'p');
    }

    public function testMarkdownPageWithMarkdownExtension(AcceptanceTester $I): void
    {
        $I->amOnPage('/formatter/markdown-2');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Markdown Page', 'h1');
        $I->see('This is a markdown formatted page.', 'p');
    }

    public function testMarkdownPageWithTextExtension(AcceptanceTester $I): void
    {
        $I->amOnPage('/formatter/markdown-3');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Markdown Page', 'h1');
        $I->see('This is a markdown formatted page.', 'p');
    }

    public function testPageWithTextileExtension(AcceptanceTester $I): void
    {
        $I->amOnPage('/formatter/textile');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Textile Page', 'h1');
        $I->see('This is a textile formatted page.', 'p');
    }

    public function testPageWithHtmExtension(AcceptanceTester $I): void
    {
        $I->amOnPage('/formatter/htm');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('HTM Page', 'h1');
        $I->see('This is a HTM formatted page.', 'p');
    }

    public function testPageWithHtmlExtension(AcceptanceTester $I): void
    {
        $I->amOnPage('/formatter/html');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('HTML Page', 'h1');
        $I->see('This is a HTML formatted page.', 'p');
    }

    public function testPageWithTextExtension(AcceptanceTester $I): void
    {
        $I->amOnPage('/formatter/text');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Text Page');
        $I->see('This is a text formatted page.');
    }

    public function testPageWithRssExtension(AcceptanceTester $I): void
    {
        $I->amOnPage('/formatter/rss');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('RSS Page');
        $I->see('This is a RSS formatted page.');
    }

    public function testMarkdownPageWithRstExtension(AcceptanceTester $I): void
    {
        $I->amOnPage('/formatter/rest');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('REST Page', 'h1');
        $I->see('This is a reStructuredText formatted page.', 'p');
    }

    public function testPageWithXmlExtension(AcceptanceTester $I): void
    {
        $I->amOnPage('/formatter/xml');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('XML Page');
        $I->see('This is a XML formatted page.');
    }

    public function testPageWithWrongExtension(AcceptanceTester $I): void
    {
        $I->amOnPage('/formatter/invalid');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->see('Page not found');
        $I->see('could not be found');
    }

    public function testTextPageWithVariousFormattings(AcceptanceTester $I): void
    {
        $I->amOnPage('/formatter/various');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Markdown Filter', 'h2');
        $I->see('Markdown Function', 'h2');
        $I->see('reStructuredText Filter', 'h1');
        $I->see('reStructuredText Function', 'h1');
        $I->see('Textile Filter', 'h2');
        $I->see('Textile Function', 'h2');
    }
}
