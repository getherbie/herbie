<?php

namespace Tests\Acceptance;

use AcceptanceTester;
use Codeception\Util\HttpCode;

class DummyPluginCest
{
    public function testDummyPlugin(AcceptanceTester $I)
    {
        /** @var \Codeception\Lib\Interfaces\Web $I */
        $I->amOnPage('/plugins/dummy');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeInTitle('Dummy Plugin');
        $I->see('Dummy Plugin', 'h1');
        $I->seeElement('.dummy-plugin-render-segment');
        $I->seeElement('.dummy-plugin-render-content');
        $I->seeElement('.dummy-plugin-render-layout');
        $I->see('This is from Dummy Filter.', 'p');
        $I->see('This is from Dummy Filter Dynamic.', 'p');
        $I->see('This is from Dummy Function.', 'p');
        $I->see('This is from Dummy Test.', 'p');
        $I->see('This is from Dummy Middleware.', 'p');
        // TODO complete tests
    }
}
