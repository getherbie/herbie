<?php

namespace Tests\Acceptance;

use AcceptanceTester;
use Codeception\Util\HttpCode;

class DummyPluginCest
{
    public function testMarkdownPageWithMdExtension(AcceptanceTester $I)
    {
        /** @var \Codeception\Lib\Interfaces\Web $I */
        $I->amOnPage('/tests/plugins/dummy');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeInTitle('Dummy Plugin');
        $I->see('Dummy Plugin', 'h1');
        $I->seeElement('.dummy-plugin-render-segment');
        $I->seeElement('.dummy-plugin-render-content');
        $I->seeElement('.dummy-plugin-render-layout');
        
        // TODO complete tests
    }
}
