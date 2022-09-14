<?php

class PageFormatterCest
{
    public function testMarkdownPage(AcceptanceTester $I)
    {
        $I->amOnPage('/tests/formatter/markdown');
        $I->see('Markdown Testpage', 'h1');
        $I->see('Hello, this is markdown.', 'p');
    }

    public function testTextilePage(AcceptanceTester $I)
    {
        $I->amOnPage('/tests/formatter/textile');
        $I->see('Textile Testpage', 'h1');
        $I->see('Hello, this is textile.', 'p');
    }
}
