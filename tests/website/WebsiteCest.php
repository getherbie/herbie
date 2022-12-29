<?php

namespace herbie\tests\acceptance;

use AcceptanceTester;
use Codeception\Lib\Interfaces\Web;
use Codeception\Util\HttpCode;

final class WebsiteCest
{
    public function testHomepage(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeInTitle('Herbie CMS - A simple flat-file content management system');
        $I->see('Herbie', 'h1');
        $I->see('Featuring', 'h2');
        $this->testDocLayouts($I);
    }

    public function testDoc(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc'); // redirects to /doc/introduction
        $I->seeResponseCodeIs(HttpCode::OK);
        // if we see this, redirect was successful
        $I->seeInTitle('Introduction / Documentation / Herbie CMS');
        $I->see('Documentation', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.toc-links li', 2);
        $I->seeNumberOfElements('.content>h2', 2);
        $I->seeNumberOfElements('.content>p', 4);
    }

    public function testDocFirstSteps(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/first-steps'); // redirects to /doc/first-steps/quickstart
        $I->seeResponseCodeIs(HttpCode::OK);
        // if we see this, redirect was successful
        $I->see('Quickstart', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>p', 6);
        $I->seeNumberOfElements('.content>pre', 1);
    }

    public function testDocFirstStepsInstallation(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/first-steps/installation');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Installation', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>h2', 2);
        $I->seeNumberOfElements('.content>p', 6);
        $I->seeNumberOfElements('.content>pre', 3);
    }

    public function testDocFirstStepsConfiguration(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/first-steps/configuration');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Configuration', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>h2', 3);
        $I->seeNumberOfElements('.content>p', 11);
        $I->seeNumberOfElements('.content>pre', 4);
    }

    public function testDocFirstStepsDirectory(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/first-steps/directory');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Directory structure', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>h2', 2);
        $I->seeNumberOfElements('.content>p', 7);
        $I->seeNumberOfElements('.content>pre', 3);
        $I->seeNumberOfElements('.content>table', 2);
    }

    public function testDocContents(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents'); // redirects to 1st subpage
        $I->seeResponseCodeIs(HttpCode::OK);
        // if we see this, redirect was successful
        $I->see('Page Properties', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>h2', 2);
        $I->seeNumberOfElements('.content>p', 8);
        $I->seeNumberOfElements('.content>pre', 5);
        $I->seeNumberOfElements('.content>table', 1);
    }

    public function testDocContentsCreatePages(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/create-pages');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Create pages', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>h2', 8);
        $I->seeNumberOfElements('.content>p', 16);
        $I->seeNumberOfElements('.content>pre', 4);
        $I->seeNumberOfElements('.content>table', 1);
        $I->seeNumberOfElements('.content>ul', 1);
    }

    public function testDocContentsPageProps(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/page-props');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Structure of a page', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>h2', 2);
        $I->seeNumberOfElements('.content>p', 8);
        $I->seeNumberOfElements('.content>pre', 5);
    }

    public function testDocContentsVariables(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/variables');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Variables', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>h2', 3);
        $I->seeNumberOfElements('.content>p', 3);
        $I->seeNumberOfElements('.content>pre', 1);
        $I->seeNumberOfElements('.content>table', 3);
    }

    public function testDocContentsDataFiles(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/data-files');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Data Files', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>h2', 1);
        $I->seeNumberOfElements('.content>p', 7);
        $I->seeNumberOfElements('.content>pre', 2);
    }

    public function testDocLayouts(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/layouts');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Layouts', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>h2', 2);
        $I->seeNumberOfElements('.content>p', 14);
        $I->seeNumberOfElements('.content>pre', 7);
    }

    public function testDocIndepthConsoleCommands(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth'); // redirects to 1st subpage
        $I->seeResponseCodeIs(HttpCode::OK);
        // if we see this, redirect was successful
        $I->see('Console Commands', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>p', 5);
        $I->seeNumberOfElements('.content>pre', 3);
        $I->seeNumberOfElements('.content>table', 1);
    }

    public function testDocIndepthEventListeners(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/events');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Event Listeners', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>p', 1);
        $I->seeNumberOfElements('.content>table', 1);
    }

    public function testDocIndepthMiddlewares(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/middlewares');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Middlewares', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>h2', 2);
        $I->seeNumberOfElements('.content>p', 2);
    }

    public function testDocIndepthQueryBuilder(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/query-builder');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Query Builder', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>h2', 7);
        $I->seeNumberOfElements('.content>p', 32);
        $I->seeNumberOfElements('.content>pre', 21);
        $I->seeNumberOfElements('.content>table', 1);
    }

    public function testDocIndepthTwigFilters(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/twig-filters');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Twig Filters', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>h2', 1);
        $I->seeNumberOfElements('.content>div>h2', 3);
        $I->seeNumberOfElements('.content>p', 2);
        $I->seeNumberOfElements('.content>div>p', 3);
        #$I->seeNumberOfElements('.content>div>div', 3);
        $I->seeNumberOfElements('.content>div>pre', 3);
        $I->seeNumberOfElements('.content>div>table', 3);
        $I->seeNumberOfElements('.content>ul', 1);
        $I->seeNumberOfElements('.content>ul>li', 54);
    }

    public function testDocIndepthTwigFunctions(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/twig-functions');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Twig Functions', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>h2', 1);
        $I->seeNumberOfElements('.content>div>h2', 22);
        $I->seeNumberOfElements('.content>p', 2);
        $I->seeNumberOfElements('.content>div>p', 22);
        #$I->seeNumberOfElements('.content>div>div', 3);
        $I->seeNumberOfElements('.content>div>pre', 22);
        $I->seeNumberOfElements('.content>div>table', 22);
        $I->seeNumberOfElements('.content>ul', 1);
        $I->seeNumberOfElements('.content>ul>li', 16);
    }

    public function testDocIndepthTwigGlobals(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/twig-globals');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Twig Globals', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>h2', 3);
        $I->seeNumberOfElements('.content>p', 1);
        $I->seeNumberOfElements('.content>table', 3);
    }

    public function testDocIndepthTwigTags(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/twig-tags');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Twig Tags', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>p', 1);
        $I->seeNumberOfElements('.content>ul', 1);
        $I->seeNumberOfElements('.content>ul>li', 20);
    }

    public function testDocIndepthTwigTests(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/twig-tests');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Twig Tests', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>h2', 1);
        $I->seeNumberOfElements('.content>div>h2', 2);
        $I->seeNumberOfElements('.content>p', 2);
        $I->seeNumberOfElements('.content>div>p', 2);
        $I->seeNumberOfElements('.content>div>pre', 2);
        $I->seeNumberOfElements('.content>div>table', 2);
        $I->seeNumberOfElements('.content>ul', 1);
        $I->seeNumberOfElements('.content>ul>li', 9);
    }

    public function testDocPlugins(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/plugins');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Plugins', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>h2', 1);
        $I->seeNumberOfElements('.content>p', 3);
        $I->seeNumberOfElements('.content>pre', 1);
        $I->seeNumberOfElements('.plugins>p', 1);
        $I->seeNumberOfElements('.plugins>.plugin', 8);
    }

    public function testDocExtending(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/extending');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Extending', 'h1');
        $I->see('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>h2', 5);
        $I->seeNumberOfElements('.content>p', 38);
        $I->seeNumberOfElements('.content>pre', 24);
        $I->seeNumberOfElements('.content>table', 3);
        $I->seeNumberOfElements('.content>ul', 1);
    }

    public function testDocCheatsheet(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/cheatsheet');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Cheat Sheet', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $I->seeNumberOfElements('.content>p', 14);
        $I->seeNumberOfElements('.content>pre', 4);
        $I->seeNumberOfElements('.content>table', 7);
    }

    public function testRecipes(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/recipes');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Recipes', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $I->seeNumberOfElements('.post-title', 1);
        $this->testLayoutRecipe($I);
    }

    public function testRecipesOne(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/recipes/adding-a-sitemap-for-search-engines');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Adding a sitemap for web search engines', 'h1');
        $I->seeNumberOfElements('.content>h2', 4);
        $I->seeNumberOfElements('.content>p', 16);
        $I->seeNumberOfElements('.content>pre', 6);
        $this->testLayoutRecipe($I);
    }

    public function testDevelopment(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/development');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Development', 'h1');
        $I->seeNumberOfElements('.content>h2', 2);
        $I->seeNumberOfElements('.content>p', 4);
        $this->testDocLayouts($I);
    }

    public function testSitemap(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/sitemap');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Sitemap', 'h1');
        $I->seeNumberOfElements('.sitemap', 1);
        $I->seeNumberOfElements('.sitemap li', 31);
        $this->testLayoutDefault($I);
    }

    public function testSearch(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/search');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Search', 'h1');
        $I->seeNumberOfElements('.plugin-simplesearch-form', 1);
        $this->testLayoutDefault($I);
    }

    public function testContact(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/contact');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Contact', 'h1');
        $I->seeNumberOfElements('.simplecontact', 1);
        $I->seeElement('.simplecontact-form');
        $I->seeElement('.simplecontact-input[id=name]');
        $I->seeElement('.simplecontact-input[id=email]');
        $I->seeElement('.simplecontact-input[id=message]');
        $I->seeElement('.simplecontact-button');
        $this->testLayoutDefault($I);
    }

    public function testPrivacyPolicy(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/privacy-policy');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Datenschutzhinweise', 'h1');
        $I->seeNumberOfElements('.content>h2', 2);
        $I->seeNumberOfElements('.content>p', 7);
        $this->testLayoutDefault($I);
    }

    public function testImprint(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/imprint');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Imprint', 'h1');
        $I->seeNumberOfElements('.content>h2', 3);
        $I->seeNumberOfElements('.content>p', 8);
        $this->testLayoutDefault($I);
    }

    public function testAtomXml(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('index.php/atom.xml');
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function testRobotsTxt(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('index.php/robots.txt');
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function testSitemapXml(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('index.php/sitemap.xml');
        $I->seeResponseCodeIs(HttpCode::OK);
    }

    public function testError(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/not-existing-page');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->see('Page not found', 'h1');
        $I->seeNumberOfElements('.content>p', 1);
        $I->seeNumberOfElements('.sidebar-simplesearch', 1);
    }

    private function testLayoutDefault(AcceptanceTester $I)
    {
        $I->seeNumberOfElements('.sidebar-simplesearch', 1);
        $I->see('GitHub Activity', 'h4.gc-header');
        $I->seeNumberOfElements('.gc-entry', 10);
    }

    private function testLayoutRecipe(AcceptanceTester $I)
    {
        $I->seeNumberOfElements('.sidebar-simplesearch', 1);
        $I->seeNumberOfElements('.widget-blog-categories', 1);
    }
}
