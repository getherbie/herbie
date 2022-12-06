<?php

declare(strict_types=1);

namespace tests\acceptance;

use AcceptanceTester;
use Codeception\Util\HttpCode;

final class HerbieInfoCest
{
    public function testPageTitleAndResponseCode(AcceptanceTester $I)
    {
        $I->amOnPage('/herbie-info');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('System Info', 'h1');
    }

    public function testNumberAndSortingOfPhpFunctions(AcceptanceTester $I)
    {
        $functions = [
            'herbie\date_format',
            'herbie\defined_classes',
            'herbie\defined_constants',
            'herbie\defined_functions',
            'herbie\file_mtime',
            'herbie\file_read',
            'herbie\file_size',
            'herbie\get_callable_name',
            'herbie\get_constructor_params_to_inject',
            'herbie\handle_internal_webserver_assets',
            'herbie\is_digit',
            'herbie\is_natural',
            'herbie\load_composer_plugin_configs',
            'herbie\load_php_config',
            'herbie\load_plugin_config',
            'herbie\load_plugin_configs',
            'herbie\path_normalize',
            'herbie\recursive_array_replace',
            'herbie\render_exception',
            'herbie\str_explode_filtered',
            'herbie\str_leading_slash',
            'herbie\str_trailing_slash',
            'herbie\str_unleading_slash',
            'herbie\str_untrailing_slash',
            'herbie\time_format',
            'herbie\time_from_string',
        ];
        $I->amOnPage('/herbie-info');
        $I->see('PHP Functions (' . count($functions) . ')', 'h2');
        $I->see(join(',', $functions), '.herbie-info-php-functions');
    }

    public function testNumberAndSortingOfPhpClasses(AcceptanceTester $I)
    {
        $classes = [
            'herbie\AbstractEvent',
            'herbie\Alias',
            'herbie\Application',
            'herbie\ApplicationExtensionsPlugin',
            'herbie\ApplicationPaths',
            'herbie\Assets',
            'herbie\CallableMiddleware',
            'herbie\Config',
            'herbie\Container',
            'herbie\ContainerBuilder',
            'herbie\CorePlugin',
            'herbie\DownloadMiddleware',
            'herbie\ErrorHandlerMiddleware',
            'herbie\EventManager',
            'herbie\FileInfo',
            'herbie\FileInfoFilterCallback',
            'herbie\FileInfoSortableIterator',
            'herbie\FlatFileIterator',
            'herbie\FlatFilePagePersistence',
            'herbie\FlatFilePageRepository',
            'herbie\HttpBasicAuthMiddleware',
            'herbie\InstallablePlugin',
            'herbie\JsonDataRepository',
            'herbie\LocalExtensionsPlugin',
            'herbie\MiddlewareDispatcher',
            'herbie\NullCache',
            'herbie\Page',
            'herbie\PageFactory',
            'herbie\PageList',
            'herbie\PageRendererMiddleware',
            'herbie\PageResolverMiddleware',
            'herbie\Plugin',
            'herbie\PluginManager',
            'herbie\RecursiveDirectoryIterator',
            'herbie\ResponseTimeMiddleware',
            'herbie\Site',
            'herbie\SystemInfoPlugin',
            'herbie\Translator',
            'herbie\TwigRenderer',
            'herbie\TwigStringLoader',
            'herbie\UncaughtExceptionHandler',
            'herbie\UrlManager',
            'herbie\Yaml',
            'herbie\event\PluginsInitializedEvent',
            'herbie\event\RenderPageEvent',
            'herbie\event\RenderSegmentEvent',
            'herbie\event\TranslatorInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\sysplugin\dummy\DummySysPlugin',
            'herbie\sysplugin\imagine\ImagineSysPlugin',
            'herbie\sysplugin\markdown\MarkdownSysPlugin',
            'herbie\sysplugin\rest\RestSysPlugin',
            'herbie\sysplugin\textile\TextileSysPlugin',
            'herbie\sysplugin\twig\TwigExtension',
            'herbie\sysplugin\twig\TwigPlugin',
        ];
        $I->amOnPage('/herbie-info');
        $I->see('PHP Classes (' . count($classes) . ')', 'h2');
        $I->see(join(',', $classes), '.herbie-info-php-classes');
    }

    public function testNumberAndSortingOfMiddlewares(AcceptanceTester $I)
    {
        $middlewares = [
            'herbie\ErrorHandlerMiddleware',
            'herbie\PageResolverMiddleware',
            'herbie\sysplugin\dummy\DummySysPlugin->appMiddleware',
            'tests\_data\src\CustomHeader',
            'herbie\ResponseTimeMiddleware',
            'tests\_data\src\CustomHeader',
            'tests\_data\src\CustomHeader',
            'tests\_data\src\CustomHeader',
            'herbie\sysplugin\dummy\DummySysPlugin->routeMiddleware',
            'herbie\sysplugin\dummy\DummySysPlugin->routeMiddleware',
            'tests\_data\src\CustomHeader',
            'tests\_data\src\CustomHeader',
            'tests\_data\src\CustomHeader',
            'herbie\HttpBasicAuthMiddleware',
            'herbie\DownloadMiddleware',
            'herbie\PageRendererMiddleware',
        ];
        $I->amOnPage('/herbie-info');
        $I->see('Middlewares (' . count($middlewares) . ')', 'h2');
        $I->see(join(',', $middlewares), '.herbie-info-twig-middlewares');
    }

    public function testNumberAndSortingOfConfig(AcceptanceTester $I)
    {
        $configs = [
            'charset',
            'components.dataRepository.adapter',
            'components.downloadMiddleware.route',
            'components.downloadMiddleware.storagePath',
            'components.fileCache',
            'components.fileLogger',
            'components.flatFilePagePersistence.cache',
            'components.flatFilePagePersistence.cacheTTL',
            'components.pageRendererMiddleware.cache',
            'components.pageRendererMiddleware.cacheTTL',
            'components.twigRenderer.autoescape',
            'components.twigRenderer.cache',
            'components.twigRenderer.charset',
            'components.twigRenderer.debug',
            'components.twigRenderer.strictVariables',
            'components.urlManager.niceUrls',
            'enabledPlugins',
            'enabledSysPlugins',
            'fileExtensions.layouts',
            'fileExtensions.pages',
            'language',
            'locale',
            'paths.app',
            'paths.data',
            'paths.media',
            'paths.pages',
            'paths.plugins',
            'paths.site',
            'paths.themes',
            'paths.web',
            'plugins.CORE.enableTwigInLayoutFilter',
            'plugins.CORE.enableTwigInSegmentFilter',
            'plugins.LOCAL_EXT.pathApplicationMiddlewares',
            'plugins.LOCAL_EXT.pathConsoleCommands',
            'plugins.LOCAL_EXT.pathEventListeners',
            'plugins.LOCAL_EXT.pathRouteMiddlewares',
            'plugins.LOCAL_EXT.pathTwigFilters',
            'plugins.LOCAL_EXT.pathTwigFunctions',
            'plugins.LOCAL_EXT.pathTwigGlobals',
            'plugins.LOCAL_EXT.pathTwigTests',
            'plugins.dummy.apiVersion',
            'plugins.dummy.location',
            'plugins.dummy.pluginClass',
            'plugins.dummy.pluginName',
            'plugins.dummy.pluginPath',
            'plugins.dummy2.apiVersion',
            'plugins.dummy2.location',
            'plugins.dummy2.pluginClass',
            'plugins.dummy2.pluginName',
            'plugins.dummy2.pluginPath',
            'plugins.imagine.apiVersion',
            'plugins.imagine.cachePath',
            'plugins.imagine.filterSets.bsp1.filters.thumbnail.mode',
            'plugins.imagine.filterSets.bsp1.filters.thumbnail.size.0',
            'plugins.imagine.filterSets.bsp1.filters.thumbnail.size.1',
            'plugins.imagine.filterSets.bsp10.filters.flipHorizontally',
            'plugins.imagine.filterSets.bsp10.filters.thumbnail.mode',
            'plugins.imagine.filterSets.bsp10.filters.thumbnail.size.0',
            'plugins.imagine.filterSets.bsp10.filters.thumbnail.size.1',
            'plugins.imagine.filterSets.bsp11.filters.resize.size.0',
            'plugins.imagine.filterSets.bsp11.filters.resize.size.1',
            'plugins.imagine.filterSets.bsp12.filters.thumbnail.mode',
            'plugins.imagine.filterSets.bsp12.filters.thumbnail.size.0',
            'plugins.imagine.filterSets.bsp12.filters.thumbnail.size.1',
            'plugins.imagine.filterSets.bsp12.filters.upscale.min.0',
            'plugins.imagine.filterSets.bsp12.filters.upscale.min.1',
            'plugins.imagine.filterSets.bsp13.filters.relativeResize.method',
            'plugins.imagine.filterSets.bsp13.filters.relativeResize.parameter',
            'plugins.imagine.filterSets.bsp14.filters.relativeResize.method',
            'plugins.imagine.filterSets.bsp14.filters.relativeResize.parameter',
            'plugins.imagine.filterSets.bsp15.filters.relativeResize.method',
            'plugins.imagine.filterSets.bsp15.filters.relativeResize.parameter',
            'plugins.imagine.filterSets.bsp15.filters.thumbnail.mode',
            'plugins.imagine.filterSets.bsp15.filters.thumbnail.size.0',
            'plugins.imagine.filterSets.bsp15.filters.thumbnail.size.1',
            'plugins.imagine.filterSets.bsp16.filters.relativeResize.method',
            'plugins.imagine.filterSets.bsp16.filters.relativeResize.parameter',
            'plugins.imagine.filterSets.bsp16.filters.thumbnail.mode',
            'plugins.imagine.filterSets.bsp16.filters.thumbnail.size.0',
            'plugins.imagine.filterSets.bsp16.filters.thumbnail.size.1',
            'plugins.imagine.filterSets.bsp17.filters.blur.sigma',
            'plugins.imagine.filterSets.bsp17.filters.thumbnail.mode',
            'plugins.imagine.filterSets.bsp17.filters.thumbnail.size.0',
            'plugins.imagine.filterSets.bsp17.filters.thumbnail.size.1',
            'plugins.imagine.filterSets.bsp2.filters.crop.size.0',
            'plugins.imagine.filterSets.bsp2.filters.crop.size.1',
            'plugins.imagine.filterSets.bsp2.filters.crop.start.0',
            'plugins.imagine.filterSets.bsp2.filters.crop.start.1',
            'plugins.imagine.filterSets.bsp2.filters.thumbnail.mode',
            'plugins.imagine.filterSets.bsp2.filters.thumbnail.size.0',
            'plugins.imagine.filterSets.bsp2.filters.thumbnail.size.1',
            'plugins.imagine.filterSets.bsp3.filters.grayscale',
            'plugins.imagine.filterSets.bsp3.filters.thumbnail.mode',
            'plugins.imagine.filterSets.bsp3.filters.thumbnail.size.0',
            'plugins.imagine.filterSets.bsp3.filters.thumbnail.size.1',
            'plugins.imagine.filterSets.bsp4.filters.colorize.color',
            'plugins.imagine.filterSets.bsp4.filters.thumbnail.mode',
            'plugins.imagine.filterSets.bsp4.filters.thumbnail.size.0',
            'plugins.imagine.filterSets.bsp4.filters.thumbnail.size.1',
            'plugins.imagine.filterSets.bsp5.filters.negative',
            'plugins.imagine.filterSets.bsp5.filters.thumbnail.mode',
            'plugins.imagine.filterSets.bsp5.filters.thumbnail.size.0',
            'plugins.imagine.filterSets.bsp5.filters.thumbnail.size.1',
            'plugins.imagine.filterSets.bsp6.filters.sharpen',
            'plugins.imagine.filterSets.bsp6.filters.thumbnail.mode',
            'plugins.imagine.filterSets.bsp6.filters.thumbnail.size.0',
            'plugins.imagine.filterSets.bsp6.filters.thumbnail.size.1',
            'plugins.imagine.filterSets.bsp7.filters.gamma.correction',
            'plugins.imagine.filterSets.bsp7.filters.thumbnail.mode',
            'plugins.imagine.filterSets.bsp7.filters.thumbnail.size.0',
            'plugins.imagine.filterSets.bsp7.filters.thumbnail.size.1',
            'plugins.imagine.filterSets.bsp8.filters.rotate.angle',
            'plugins.imagine.filterSets.bsp8.filters.thumbnail.mode',
            'plugins.imagine.filterSets.bsp8.filters.thumbnail.size.0',
            'plugins.imagine.filterSets.bsp8.filters.thumbnail.size.1',
            'plugins.imagine.filterSets.bsp9.filters.flipVertically',
            'plugins.imagine.filterSets.bsp9.filters.thumbnail.mode',
            'plugins.imagine.filterSets.bsp9.filters.thumbnail.size.0',
            'plugins.imagine.filterSets.bsp9.filters.thumbnail.size.1',
            'plugins.imagine.filterSets.default.filters.thumbnail.mode',
            'plugins.imagine.filterSets.default.filters.thumbnail.size.0',
            'plugins.imagine.filterSets.default.filters.thumbnail.size.1',
            'plugins.imagine.filterSets.default.test',
            'plugins.imagine.location',
            'plugins.imagine.pluginClass',
            'plugins.imagine.pluginName',
            'plugins.imagine.pluginPath',
            'plugins.imagine.test',
            'plugins.markdown.apiVersion',
            'plugins.markdown.enableTwigFilter',
            'plugins.markdown.enableTwigFunction',
            'plugins.markdown.location',
            'plugins.markdown.pluginClass',
            'plugins.markdown.pluginName',
            'plugins.markdown.pluginPath',
            'plugins.rest.apiVersion',
            'plugins.rest.enableTwigFilter',
            'plugins.rest.enableTwigFunction',
            'plugins.rest.location',
            'plugins.rest.pluginClass',
            'plugins.rest.pluginName',
            'plugins.rest.pluginPath',
            'plugins.simplecontact.apiVersion',
            'plugins.simplecontact.config.recipient',
            'plugins.simplecontact.config.template',
            'plugins.simplecontact.location',
            'plugins.simplecontact.pluginClass',
            'plugins.simplecontact.pluginName',
            'plugins.simplecontact.pluginPath',
            'plugins.simplesearch.apiVersion',
            'plugins.simplesearch.config.formTemplate',
            'plugins.simplesearch.config.resultsTemplate',
            'plugins.simplesearch.config.usePageCache',
            'plugins.simplesearch.location',
            'plugins.simplesearch.pluginClass',
            'plugins.simplesearch.pluginName',
            'plugins.simplesearch.pluginPath',
            'plugins.textile.apiVersion',
            'plugins.textile.enableTwigFilter',
            'plugins.textile.enableTwigFunction',
            'plugins.textile.location',
            'plugins.textile.pluginClass',
            'plugins.textile.pluginName',
            'plugins.textile.pluginPath',
            'plugins.twig.apiVersion',
            'plugins.twig.location',
            'plugins.twig.pluginClass',
            'plugins.twig.pluginName',
            'plugins.twig.pluginPath',
            'theme',
            'urls.media',
            'urls.web',
        ];
        $I->amOnPage('/herbie-info');
        $I->see('Configuration (' . count($configs) . ')', 'h2');
        $I->see(join(',', $configs), '.herbie-info-config');
    }

    public function testNumberAndSortingOfTwigGlobals(AcceptanceTester $I)
    {
        $globals = [
            'route',
            'routeParams',
            'baseUrl',
            'theme',
            'site',
            'page',
            'config',
            'dummy',
            'timestamp'
        ];
        $I->amOnPage('/herbie-info');
        $I->see('Twig Globals (' . count($globals) . ')', 'h2');
        $I->see(join(',', $globals), '.herbie-info-twig-globals');
    }

    public function testNumberAndSortingOfTwigFilters(AcceptanceTester $I)
    {
        $filters = [
            'date',
            'date_modify',
            'format',
            'replace',
            'number_format',
            'abs',
            'round',
            'url_encode',
            'json_encode',
            'convert_encoding',
            'title',
            'capitalize',
            'upper',
            'lower',
            'striptags',
            'trim',
            'nl2br',
            'spaceless',
            'join',
            'split',
            'sort',
            'merge',
            'batch',
            'column',
            'filter',
            'map',
            'reduce',
            'reverse',
            'length',
            'slice',
            'first',
            'last',
            'default',
            'keys',
            'escape',
            'e',
            'raw',
            'filesize',
            'find',
            'slugify',
            'strftime',
            'visible',
            'markdown',
            'rest',
            'textile',
            'imagine',
            'dummy',
            'dummy_dynamic',
            'local_reverse',
            'my_filter2',
            'myfilter',
            'my_filter',
        ];
        $I->amOnPage('/herbie-info');
        $I->see('Twig Filters (' . count($filters) . ')', 'h2');
        $I->see(join(',', $filters), '.herbie-info-twig-filters');
    }

    public function testNumberAndSortingOfTwigFunctions(AcceptanceTester $I)
    {
        $functions = [
            'max',
            'min',
            'range',
            'constant',
            'cycle',
            'random',
            'date',
            'include',
            'source',
            'dump',
            'add_css',
            'add_js',
            'css_classes',
            'file_link',
            'file',
            'image',
            'menu_ascii_tree',
            'menu_breadcrumb',
            'menu_list',
            'menu_pager',
            'menu_sitemap',
            'menu_tree',
            'page_link',
            'page_title',
            'output_css',
            'output_js',
            'snippet',
            'translate',
            'url',
            'abs_url',
            'mail_link',
            'herbie_debug',
            'markdown',
            'rest',
            'textile',
            'imagine',
            'dummy',
            'local_hello',
            'myfunction',
            'herbie_info',
        ];
        $I->amOnPage('/herbie-info');
        $I->see('Twig Functions (' . count($functions) . ')', 'h2');
        $I->see(join(',', $functions), '.herbie-info-twig-functions');
    }

    public function testNumberAndSortingOfTwigTests(AcceptanceTester $I)
    {
        $tests = [
            'even',
            'odd',
            'defined',
            'same as',
            'none',
            'null',
            'divisible by',
            'constant',
            'empty',
            'iterable',
            'readable',
            'writable',
            'dummy',
            'local_odd',
            'mytest',
        ];
        $I->amOnPage('/herbie-info');
        $I->see('Twig Tests (' . count($tests) . ')', 'h2');
        $I->see(join(',', $tests), '.herbie-info-twig-tests');
    }

    public function testNumberAndSortingOfEvents(AcceptanceTester $I)
    {
        $events = [
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\TwigInitializedEvent',
            'herbie\event\RenderLayoutEvent',
            'herbie\event\RenderLayoutEvent',
            'herbie\event\RenderLayoutEvent',
            'herbie\event\RenderPageEvent',
            'herbie\event\RenderSegmentEvent',
            'herbie\event\RenderSegmentEvent',
            'herbie\event\RenderSegmentEvent',
            'herbie\event\RenderSegmentEvent',
            'herbie\event\RenderSegmentEvent',
            'herbie\event\RenderSegmentEvent',
            'herbie\event\RenderSegmentEvent',
            'herbie\event\ContentRenderedEvent',
            'herbie\event\LayoutRenderedEvent',
            'herbie\event\PluginsInitializedEvent',
            'herbie\event\ResponseEmittedEvent',
            'herbie\event\ResponseGeneratedEvent',
            'herbie\event\TranslatorInitializedEvent',
        ];
        $I->amOnPage('/herbie-info');
        $I->see('Event Listeners (' . count($events) . ')', 'h2');
        $I->see(join(',', $events), '.herbie-info-events');
    }

    public function testNumberAndSortingOfPlugins(AcceptanceTester $I)
    {
        $plugins = [
            'CORE',
            'twig',
            'markdown',
            'rest',
            'textile',
            'imagine',
            'dummy',
            'LOCAL_EXT',
            'APP_EXT',
            'SYS_INFO',
        ];
        $I->amOnPage('/herbie-info');
        $I->see('Plugins (' . count($plugins) . ')', 'h2');
        $I->see(join(',', $plugins), '.herbie-info-plugins');
    }

    public function testNumberAndSortingOfCommands(AcceptanceTester $I)
    {
        $commands = [
            'herbie\ClearFilesCommand',
            'herbie\sysplugin\dummy\DummyCommand',
            'tests\_data\site\extend\commands\CustomCommand',
            'tests\_data\src\CustomCommand',
            'tests\_data\src\CustomCommand'
        ];
        $I->amOnPage('/herbie-info');
        $I->see('Commands (' . count($commands) . ')', 'h2');
        $I->see(join(',', $commands), '.herbie-info-commands');
    }

    public function testNumberAndSortingOfAliases(AcceptanceTester $I)
    {
        $aliases = [
            '@app',
            '@asset',
            '@media',
            '@page',
            '@plugin',
            '@site',
            '@sysplugin',
            '@vendor',
            '@web',
            '@snippet'
        ];
        $I->amOnPage('/herbie-info');
        $I->see('Aliases (' . count($aliases) . ')', 'h2');
        $I->see(join(',', $aliases), '.herbie-info-aliases');
    }
}
