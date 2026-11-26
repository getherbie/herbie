<?php

namespace herbie\tests\acceptance;

use AcceptanceTester;
use Codeception\Lib\Interfaces\Web;
use Codeception\Util\HttpCode;
use herbie\FileCache;

final class WebsiteCest
{
    public function _after(AcceptanceTester $I): void
    {
        $path = dirname(__DIR__, 2) . '/website/site/runtime/cache/system';
        if (is_dir($path)) {
            (new FileCache($path))->clear();
        }
    }

    public function testHomepage(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeInTitle('Herbie CMS - A simple flat-file content management system');
        $I->see('Websites built from text files.', 'h1');
        $I->see('What Herbie Is Built On', 'h2');
    }

    public function testDoc(AcceptanceTester $I): void
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

    public function testDocIntroduction(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/introduction');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeInTitle('Introduction / Documentation / Herbie CMS');
        $I->see('Introduction', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocFirstSteps(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/first-steps');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('First Steps', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $I->seeLink('Quickstart', 'doc/first-steps/quickstart');
    }

    public function testDocFirstStepsQuickstart(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/first-steps/quickstart');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Quickstart', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocFirstStepsInstallation(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/first-steps/installation');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Installation', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocFirstStepsConfiguration(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/first-steps/configuration');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Configuration', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocFirstStepsDirectory(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/first-steps/directory');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Directory structure', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocContents(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Contents', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $I->seeLink('Page Properties', 'doc/contents/page-properties');
    }

    public function testDocContentsPageProperties(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/page-properties');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Page Properties', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocContentsCreatePages(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/create-pages');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Create Pages', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocContentsPageProps(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/page-props');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Structure of a Page', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocContentsVariables(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/variables');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Variables', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocContentsDataFiles(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/data-files');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Data Files', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocContentsMarkdown(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/markdown');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Markdown', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocContentsSpecialPages(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/contents/special-pages');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Special Pages', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocLayouts(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/layouts');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Themes and layouts', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocIndepth(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Indepth', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $I->seeLink('Console Commands', 'doc/indepth/commands');
    }

    public function testDocIndepthCommands(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/commands');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Console Commands', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocIndepthEventListeners(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/events');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Event Listeners', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocIndepthMiddlewares(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/middlewares');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Middlewares', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocIndepthQueryBuilder(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/query-builder');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Query Builder', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocIndepthTwigFilters(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/twig-filters');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Twig Filters', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $I->seeLink('official Twig documentation', 'https://twig.symfony.com/doc/3.x/filters/index.html');
    }

    public function testDocIndepthTwigFunctions(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/twig-functions');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Twig Functions', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $I->seeLink('official Twig documentation', 'https://twig.symfony.com/doc/3.x/functions/index.html');
    }

    public function testDocIndepthTwigGlobals(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/twig-globals');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Twig Globals', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDocIndepthTwigTags(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/twig-tags');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Twig Tags', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $I->seeLink('official Twig documentation', 'https://twig.symfony.com/doc/3.x/tags/index.html');
    }

    public function testDocIndepthTwigTests(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/indepth/twig-tests');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Twig Tests', 'h1');
        $I->see('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $I->seeLink('official Twig documentation', 'https://twig.symfony.com/doc/3.x/tests/index.html');
    }

    public function testDocPlugins(AcceptanceTester $I): void
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

    public function testDocExtending(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/extending');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Extending', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $pages = [
            'What can be extended' => 'extension-points',
            'Files in site/extend' => 'files',
            'Register in index.php' => 'bootstrap',
            'Plugin class' => 'plugin-class',
            'Composer package' => 'composer-package',
            'Example plugins' => 'examples',
        ];
        foreach ($pages as $title => $route) {
            $I->seeLink($title, 'doc/extending/' . $route);
            $I->see($title, '.sidebar .menu');
        }
    }

    public function testDocExtendingExtensionPoints(AcceptanceTester $I): void
    {
        $this->seeExtendingPage($I, 'extension-points', 'What can be extended');
    }

    public function testDocExtendingFilesystem(AcceptanceTester $I): void
    {
        $this->seeExtendingPage($I, 'files', 'Files in site/extend');
    }

    public function testDocExtendingProgrammatic(AcceptanceTester $I): void
    {
        $this->seeExtendingPage($I, 'bootstrap', 'Register in index.php');
    }

    public function testDocExtendingPlugin(AcceptanceTester $I): void
    {
        $this->seeExtendingPage($I, 'plugin-class', 'Plugin class', false);
    }

    public function testDocExtendingDistributedPlugin(AcceptanceTester $I): void
    {
        $this->seeExtendingPage($I, 'composer-package', 'Composer package', false);
    }

    public function testDocExtendingPluginExamples(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/extending/examples');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Example plugins', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
        $I->seeElement('.content ul');
        $titles = [
            'random' => 'Random Page',
            'gist' => 'GitHub Gist',
            'mathjax' => 'MathJax Renderer',
            'grid' => 'Grid System',
            'disqus' => 'Disqus Comments',
            'video' => 'Video Embeds',
            'lipsum' => 'Lipsum Generator',
        ];
        foreach ($titles as $name => $title) {
            $I->seeLink($title, 'doc/extending/examples/' . $name);
        }
    }

    public function testDocExtendingPluginExampleRandom(AcceptanceTester $I): void
    {
        $this->seePluginExample($I, 'random', 'Random Page');
    }

    public function testDocExtendingPluginExampleGist(AcceptanceTester $I): void
    {
        $this->seePluginExample($I, 'gist', 'GitHub Gist');
    }

    public function testDocExtendingPluginExampleMathjax(AcceptanceTester $I): void
    {
        $this->seePluginExample($I, 'mathjax', 'MathJax Renderer');
    }

    public function testDocExtendingPluginExampleGrid(AcceptanceTester $I): void
    {
        $this->seePluginExample($I, 'grid', 'Grid System');
    }

    public function testDocExtendingPluginExampleDisqus(AcceptanceTester $I): void
    {
        $this->seePluginExample($I, 'disqus', 'Disqus Comments');
    }

    public function testDocExtendingPluginExampleVideo(AcceptanceTester $I): void
    {
        $this->seePluginExample($I, 'video', 'Video Embeds');
    }

    public function testDocExtendingPluginExampleLipsum(AcceptanceTester $I): void
    {
        $this->seePluginExample($I, 'lipsum', 'Lipsum Generator');
    }

    public function testDocCheatsheet(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/cheatsheet');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Cheat Sheet', 'h1');
        $I->dontSee('On this page', '.toc-title');
        $this->seeRenderedContent($I);
    }

    public function testDevelopment(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/development');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Development', 'h1');
    }

    public function testSitemap(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/sitemap');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Sitemap', 'h1');
        $I->seeElement('.sitemap');
        // The URL form depends on urlManager.niceUrls from the local config.
        $I->seeLink('Quickstart');
        $I->seeLink('Cheat Sheet');
        $this->testLayoutDefault($I);
    }

    public function testSearch(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/search');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Search', 'h1');
        $I->seeNumberOfElements('.plugin-search-form', 1);
        $this->testLayoutDefault($I);
    }

    public function testContact(AcceptanceTester $I): void
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

    public function testPrivacyPolicy(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/privacy-policy');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Privacy Policy', 'h1');
        $this->testLayoutDefault($I);
    }

    public function testImprint(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/imprint');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Imprint', 'h1');
        $this->testLayoutDefault($I);
    }

    public function testAtomXml(AcceptanceTester $I): void
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

    public function testRobotsTxt(AcceptanceTester $I): void
    {
        /** @var Web $I */
        foreach (['index.php/robots.txt', '/robots.txt'] as $url) { // dev and production URL form
            $I->amOnPage($url);
            $I->seeResponseCodeIs(HttpCode::OK);
            $I->seeResponseContains('Sitemap:');
            $I->seeResponseContains('sitemap.xml');
        }
    }

    public function testSitemapXml(AcceptanceTester $I): void
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

    public function testError(AcceptanceTester $I): void
    {
        /** @var Web $I */
        $I->amOnPage('/not-existing-page');
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND);
        $I->see('Page not found', 'h1');
        $I->seeNumberOfElements('.sidebar-search', 1);
    }

    private function seeRenderedContent(AcceptanceTester $I): void
    {
        $I->seeElement('.content h1');
        $I->seeElement('.content p, .content pre, .content table, .content ul');
    }

    private function seeExtendingPage(AcceptanceTester $I, string $route, string $title, bool $toc = true): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/extending/' . $route);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see($title, 'h1');
        if ($toc) {
            $I->see('On this page', '.toc-title');
        } else {
            $I->dontSee('On this page', '.toc-title');
        }
        $this->seeRenderedContent($I);
    }

    private function seePluginExample(AcceptanceTester $I, string $name, string $title): void
    {
        /** @var Web $I */
        $I->amOnPage('/doc/extending/examples/' . $name);
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see($title, 'h1');
        $I->seeInTitle($title);
        $I->see($title, '.sidebar .menu');
        $I->see('On this page', '.toc-title');
        $I->see('Usage', 'h2');
        $this->seeRenderedContent($I);
    }

    private function testLayoutDefault(AcceptanceTester $I): void
    {
        $I->seeNumberOfElements('.sidebar-search', 1);
        $I->see('GitHub Activity', 'h4.gc-header');
        $I->see('Fixture commit', '.gc-entry-title');
    }

}
