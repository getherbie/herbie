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
        $I->see('Websites built from text files.', 'h1');
        $I->see('What Herbie Is Built On', 'h2');
    }

    public function testDoc(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc'); // redirects to /doc/introduction
        $I->seeResponseCodeIs(HttpCode::OK);
        // if we see this, redirect was successful
        $I->seeInTitle('Introduction / Documentation / Herbie CMS');
        $I->see('Introduction', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $I->seeElement('.toc-links li');
    }

    public function testDocIntroduction(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/introduction');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeInTitle('Introduction / Documentation / Herbie CMS');
        $I->see('Introduction', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocFirstSteps(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/first-steps');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('First Steps', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $I->seeLink('Quickstart', 'doc/first-steps/quickstart');
    }

    public function testDocFirstStepsQuickstart(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/first-steps/quickstart');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Quickstart', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocFirstStepsInstallation(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/first-steps/installation');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Installation', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocFirstStepsConfiguration(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/first-steps/configuration');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Configuration', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocFirstStepsDirectory(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/first-steps/directory');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Directory structure', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocContents(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Contents', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $I->seeLink('Page Properties', 'doc/contents/page-properties');
    }

    public function testDocContentsPageProperties(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/page-properties');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Page Properties', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocContentsCreatePages(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/create-pages');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Create Pages', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocContentsPageProps(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/page-props');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Structure of a Page', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocContentsVariables(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/variables');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Variables', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocContentsDataFiles(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/data-files');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Data Files', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocContentsMarkdown(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/markdown');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Markdown', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocContentsSpecialPages(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/special-pages');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Special Pages', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocLayouts(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/layouts');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Themes and layouts', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocIndepth(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Indepth', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $I->seeLink('Console Commands', 'doc/indepth/commands');
    }

    public function testDocIndepthCommands(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/commands');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Console Commands', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocIndepthEventListeners(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/events');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Event Listeners', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocIndepthMiddlewares(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/middlewares');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Middlewares', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocIndepthQueryBuilder(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/query-builder');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Query Builder', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocIndepthTwigFilters(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/twig-filters');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Twig Filters', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $I->seeLink('official Twig documentation', 'https://twig.symfony.com/doc/3.x/filters/index.html');
    }

    public function testDocIndepthTwigFunctions(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/twig-functions');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Twig Functions', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $I->seeLink('official Twig documentation', 'https://twig.symfony.com/doc/3.x/functions/index.html');
    }

    public function testDocIndepthTwigGlobals(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/twig-globals');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Twig Globals', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocIndepthTwigTags(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/twig-tags');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Twig Tags', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $I->seeLink('official Twig documentation', 'https://twig.symfony.com/doc/3.x/tags/index.html');
    }

    public function testDocIndepthTwigTests(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/twig-tests');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Twig Tests', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $I->seeLink('official Twig documentation', 'https://twig.symfony.com/doc/3.x/tests/index.html');
    }

    public function testDocPlugins(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/plugins');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Plugins', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $I->see('Contact', '.plugin');
        $I->see('Twig', '.plugin');
        $I->seeElement('a[href="https://github.com/getherbie/herbie/tree/3.x/plugins/contact"]');
        $I->seeElement('a[href="https://github.com/getherbie/herbie/tree/3.x/plugins/search"]');
    }

    public function testDocExtending(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/extending');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Extending', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocCheatsheet(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/doc/cheatsheet');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Cheat Sheet', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDevelopment(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/development');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Development', 'h1');
    }

    public function testSitemap(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/sitemap');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Sitemap', 'h1');
        $I->seeElement('.sitemap');
        $I->seeLink('Quickstart', '/index.php/doc/first-steps/quickstart');
        $I->seeLink('Cheat Sheet', '/index.php/doc/cheatsheet');
        $this->testLayoutDefault($I);
    }

    public function testSearch(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/search');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Search', 'h1');
        $I->seeNumberOfElements('.plugin-search-form', 1);
        $this->testLayoutDefault($I);
    }

    public function testContact(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/contact');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Contact us', 'h1');
        $I->seeNumberOfElements('.herbie-contact', 1);
        $I->seeElement('.herbie-contact-form');
        $I->seeElement('.herbie-contact-input[id=name]');
        $I->seeElement('.herbie-contact-input[id=email]');
        $I->seeElement('.herbie-contact-input[id=message]');
        $I->seeNumberOfElements('.herbie-contact-control-group-honeypot input.herbie-contact-input', 2);
        $I->seeElement('.herbie-contact-control-group-honeypot input.herbie-contact-input[id=phone][name=phone]');
        $I->seeElement('.herbie-contact-control-group-honeypot input.herbie-contact-input[id=website][name=website]');
        $I->seeElement('altcha-widget[challenge]');
        $I->seeElement('input[name=send_copy][type=checkbox]');
        $I->seeElement('.herbie-contact-button');
        $this->testLayoutDefault($I);
    }

    public function testPrivacyPolicy(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/privacy-policy');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Privacy Policy', 'h1');
        $this->testLayoutDefault($I);
    }

    public function testImprint(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/imprint');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Imprint', 'h1');
        $this->testLayoutDefault($I);
    }

    public function testAtomXml(AcceptanceTester $I)
    {
        /** @var Web $I */
        foreach (['index.php/atom.xml', '/atom.xml'] as $url) { // dev and production URL form
            $I->amOnPage($url);
            $I->seeResponseCodeIs(HttpCode::OK);
            $I->seeResponseContains('<feed');
            $I->seeResponseContains('Herbie CMS Feed');
            $I->seeResponseContains('<entry>');
        }
    }

    public function testRobotsTxt(AcceptanceTester $I)
    {
        /** @var Web $I */
        foreach (['index.php/robots.txt', '/robots.txt'] as $url) { // dev and production URL form
            $I->amOnPage($url);
            $I->seeResponseCodeIs(HttpCode::OK);
            $I->seeResponseContains('Sitemap:');
            $I->seeResponseContains('sitemap.xml');
        }
    }

    public function testSitemapXml(AcceptanceTester $I)
    {
        /** @var Web $I */
        foreach (['index.php/sitemap.xml', '/sitemap.xml'] as $url) { // dev and production URL form
            $I->amOnPage($url);
            $I->seeResponseCodeIs(HttpCode::OK);
            $I->seeResponseContains('<urlset');
            $I->seeResponseContains('<loc>');
            $I->seeResponseContains('/doc/introduction');
        }
    }

    public function testError(AcceptanceTester $I)
    {
        /** @var Web $I */
        $I->amOnPage('/not-existing-page');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->see('Page not found', 'h1');
        $I->seeNumberOfElements('.sidebar-search', 1);
    }

    private function seeRenderedContent(AcceptanceTester $I)
    {
        $I->seeElement('.content h1');
        $I->seeElement('.content p, .content pre, .content table, .content ul');
    }

    private function testLayoutDefault(AcceptanceTester $I)
    {
        $I->seeNumberOfElements('.sidebar-search', 1);
        $I->see('GitHub Activity', 'h4.gc-header');
        $I->see('Fixture commit', '.gc-entry-title');
    }

}
