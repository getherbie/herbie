<?php

namespace herbie\tests\acceptance;

use AcceptanceTester;
use Codeception\Util\HttpCode;

final class ImagineSysPluginCest
{
    public function testImagineSysPlugin(AcceptanceTester $I)
    {
        /** @var \Codeception\Lib\Interfaces\Web $I */
        $I->amOnPage('/plugins/imagine');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeInTitle('Imagine Plugin');

        $I->see('Default', 'h2');
        $I->seeElement('//img[@id="default"]');

        $I->see('Filter', 'h2');
        $I->seeElement('//img[@id="imagine-filter-1"]');
        $I->seeElement('//img[@id="imagine-filter-2"]');
        $I->seeElement('//img[@id="imagine-filter-3"]');
        $I->seeElement('//img[@id="imagine-filter-4"]');
        $I->seeElement('//img[@id="imagine-filter-5"]');
        $I->seeElement('//img[@id="imagine-filter-6"]');
        $I->seeElement('//img[@id="imagine-filter-7"]');
        $I->seeElement('//img[@id="imagine-filter-8"]');
        $I->seeElement('//img[@id="imagine-filter-9"]');
        $I->seeElement('//img[@id="imagine-filter-10"]');
        $I->seeElement('//img[@id="imagine-filter-11"]');
        $I->seeElement('//img[@id="imagine-filter-12"]');
        $I->seeElement('//img[@id="imagine-filter-13"]');
        $I->seeElement('//img[@id="imagine-filter-14"]');
        $I->seeElement('//img[@id="imagine-filter-15"]');
        $I->seeElement('//img[@id="imagine-filter-16"]');

        $I->see('Function', 'h2');
        $I->seeElement('//img[@id="imagine-function-1"]');
        $I->seeElement('//img[@id="imagine-function-2"]');
        $I->seeElement('//img[@id="imagine-function-3"]');
        $I->seeElement('//img[@id="imagine-function-4"]');
        $I->seeElement('//img[@id="imagine-function-5"]');
        $I->seeElement('//img[@id="imagine-function-6"]');
        $I->seeElement('//img[@id="imagine-function-7"]');
        $I->seeElement('//img[@id="imagine-function-8"]');
        $I->seeElement('//img[@id="imagine-function-9"]');
        $I->seeElement('//img[@id="imagine-function-10"]');
        $I->seeElement('//img[@id="imagine-function-11"]');
        $I->seeElement('//img[@id="imagine-function-12"]');
        $I->seeElement('//img[@id="imagine-function-13"]');
        $I->seeElement('//img[@id="imagine-function-14"]');
        $I->seeElement('//img[@id="imagine-function-15"]');
        $I->seeElement('//img[@id="imagine-function-16"]');
    }
}
