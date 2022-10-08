<?php

namespace tests\acceptance;

use AcceptanceTester;
use Codeception\Util\HttpCode;

final class DummySysPluginCest
{
    public function testDummySysPlugin(AcceptanceTester $I)
    {
        /** @var \Codeception\Lib\Interfaces\Web $I */
        $I->amOnPage('/plugins/dummy');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeInTitle('Dummy Plugin');
        $I->see('Dummy Plugin', 'h1');
        $I->seeElement('.dummy-plugin-render-segment');
        $I->seeElement('.dummy-plugin-render-content');
        $I->seeElement('.dummy-plugin-render-layout');
        $I->seeElement('.dummy-plugin-app-middleware');
        $I->seeElement('.dummy-plugin-route-middleware');
        $I->see('This is from Dummy Filter.', 'p');
        $I->see('This is from Dummy Filter Dynamic.', 'p');
        $I->see('This is from Dummy Function.', 'p');
        $I->see('This is from Dummy Test.', 'p');
        // TODO complete tests
    }
}
