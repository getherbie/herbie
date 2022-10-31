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
        $I->see('Herbie CMS Info', 'h1');
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
            'herbie\load_composer_plugin_configs',
            'herbie\load_php_config',
            'herbie\load_plugin_config',
            'herbie\load_plugin_configs',
            'herbie\path_normalize',
            'herbie\recursive_array_replace',
            'herbie\render_exception',
            'herbie\str_camelize',
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
            'herbie\Alias',
            'herbie\Application',
            'herbie\ApplicationPaths',
            'herbie\Assets',
            'herbie\CallableMiddleware',
            'herbie\Config',
            'herbie\Container',
            'herbie\ContainerBuilder',
            'herbie\DownloadMiddleware',
            'herbie\Environment',
            'herbie\ErrorHandlerMiddleware',
            'herbie\Event',
            'herbie\EventManager',
            'herbie\FileInfo',
            'herbie\FileInfoSortableIterator',
            'herbie\FilterChain',
            'herbie\FilterChainManager',
            'herbie\FilterIterator',
            'herbie\FlatfilePagePersistence',
            'herbie\FlatfilePageRepository',
            'herbie\HttpBasicAuthMiddleware',
            'herbie\InstallablePlugin',
            'herbie\JsonDataRepository',
            'herbie\MiddlewareDispatcher',
            'herbie\NullCache',
            'herbie\Page',
            'herbie\PageFactory',
            'herbie\PageItem',
            'herbie\PageList',
            'herbie\PageRendererMiddleware',
            'herbie\PageResolverMiddleware',
            'herbie\Plugin',
            'herbie\PluginManager',
            'herbie\RecursiveDirectoryIterator',
            'herbie\ResponseTimeMiddleware',
            'herbie\Site',
            'herbie\Translator',
            'herbie\TwigRenderer',
            'herbie\TwigStringLoader',
            'herbie\UncaughtExceptionHandler',
            'herbie\UrlGenerator',
            'herbie\UrlMatcher',
            'herbie\VirtualAppPlugin',
            'herbie\VirtualCorePlugin',
            'herbie\VirtualLastPlugin',
            'herbie\VirtualLocalPlugin',
            'herbie\Yaml',
            'herbie\sysplugin\dummy\DummySysPlugin',
            'herbie\sysplugin\imagine\ImagineSysPlugin',
            'herbie\sysplugin\markdown\MarkdownSysPlugin',
            'herbie\sysplugin\rest\RestSysPlugin',
            'herbie\sysplugin\textile\TextileSysPlugin',
            'herbie\sysplugin\twig_core\TwigCoreExtension',
            'herbie\sysplugin\twig_core\TwigCorePlugin',
            'herbie\sysplugin\twig_plus\TwigPlusExtension',
            'herbie\sysplugin\twig_plus\TwigPlusPlugin',
        ];
        $I->amOnPage('/herbie-info');
        $I->see('PHP Classes (' . count($classes) . ')', 'h2');
        $I->see(join(',', $classes), '.herbie-info-php-classes');
    }

    public function testNumberAndSortingOfMiddlewares(AcceptanceTester $I)
    {
        $middlewares = [
            'herbie\ErrorHandlerMiddleware',
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
            'herbie\PageResolverMiddleware',
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
            'components.downloadMiddleware.baseUrl',
            'components.downloadMiddleware.storagePath',
            'components.twigRenderer.autoescape',
            'components.twigRenderer.cache',
            'components.twigRenderer.charset',
            'components.twigRenderer.debug',
            'components.twigRenderer.strictVariables',
            'components.virtualCorePlugin.enableTwigInLayoutFilter',
            'components.virtualCorePlugin.enableTwigInSegmentFilter',
            'enabledPlugins',
            'enabledSysPlugins',
            'fileExtensions.layouts',
            'fileExtensions.pages',
            'language',
            'locale',
            'niceUrls',
            'paths.app',
            'paths.data',
            'paths.media',
            'paths.pages',
            'paths.plugins',
            'paths.site',
            'paths.themes',
            'paths.twigFilters',
            'paths.twigFunctions',
            'paths.twigGlobals',
            'paths.twigTests',
            'paths.web',
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
            'plugins.simplecontact.formConfig.errors.empty_field',
            'plugins.simplecontact.formConfig.errors.invalid_email',
            'plugins.simplecontact.formConfig.fields.antispam.label',
            'plugins.simplecontact.formConfig.fields.antispam.placeholder',
            'plugins.simplecontact.formConfig.fields.email.label',
            'plugins.simplecontact.formConfig.fields.email.placeholder',
            'plugins.simplecontact.formConfig.fields.message.label',
            'plugins.simplecontact.formConfig.fields.message.placeholder',
            'plugins.simplecontact.formConfig.fields.name.label',
            'plugins.simplecontact.formConfig.fields.name.placeholder',
            'plugins.simplecontact.formConfig.fields.submit.label',
            'plugins.simplecontact.formConfig.messages.error',
            'plugins.simplecontact.formConfig.messages.fail',
            'plugins.simplecontact.formConfig.messages.success',
            'plugins.simplecontact.formConfig.recipient',
            'plugins.simplecontact.formConfig.subject',
            'plugins.simplecontact.location',
            'plugins.simplecontact.pluginClass',
            'plugins.simplecontact.pluginName',
            'plugins.simplecontact.pluginPath',
            'plugins.simplecontact.template',
            'plugins.simplesearch.apiVersion',
            'plugins.simplesearch.formTemplate',
            'plugins.simplesearch.location',
            'plugins.simplesearch.pluginClass',
            'plugins.simplesearch.pluginName',
            'plugins.simplesearch.pluginPath',
            'plugins.simplesearch.resultsTemplate',
            'plugins.simplesearch.usePageCache',
            'plugins.textile.apiVersion',
            'plugins.textile.enableTwigFilter',
            'plugins.textile.enableTwigFunction',
            'plugins.textile.location',
            'plugins.textile.pluginClass',
            'plugins.textile.pluginName',
            'plugins.textile.pluginPath',
            'plugins.twig_core.apiVersion',
            'plugins.twig_core.location',
            'plugins.twig_core.pluginClass',
            'plugins.twig_core.pluginName',
            'plugins.twig_core.pluginPath',
            'plugins.twig_plus.apiVersion',
            'plugins.twig_plus.location',
            'plugins.twig_plus.pluginClass',
            'plugins.twig_plus.pluginName',
            'plugins.twig_plus.pluginPath',
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
            'dummy'
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
            'file_link',
            'image',
            'page_link',
            'output_css',
            'output_js',
            'translate',
            'url',
            'abs_url',
            'mail_link',
            'ascii_tree',
            'body_class',
            'breadcrumb',
            'listing',
            'menu',
            'page_taxonomies',
            'pager',
            'pages_filtered',
            'pages_recent',
            'page_title',
            'sitemap',
            'snippet',
            'taxonomy_archive',
            'taxonomy_authors',
            'taxonomy_categories',
            'taxonomy_tags',
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

    public function testNumberAndSortingOfFilters(AcceptanceTester $I)
    {
        $filters = [
            'renderLayout',
            'renderLayout',
            'renderLayout',
            'renderLayout',
            'renderSegment',
            'renderSegment',
            'renderSegment',
            'renderSegment',
            'renderSegment',
            'renderSegment',
            'renderSegment',
            'renderSegment',
            'renderSegment'
        ];
        $I->amOnPage('/herbie-info');
        $I->see('Filters (' . count($filters) . ')', 'h2');
        $I->see(join(',', $filters), '.herbie-info-filters');
    }

    public function testNumberAndSortingOfEvents(AcceptanceTester $I)
    {
        $events = [
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigInitialized',
            'onTwigAddExtension',
            'onTwigAddExtension',
            'onContentRendered',
            'onLayoutRendered',
            'onPluginsAttached',
            'onResponseEmitted',
            'onResponseGenerated',
            'onSystemPluginsAttached',
            'onComposerPluginsAttached',
            'onLocalPluginsAttached',
        ];
        $I->amOnPage('/herbie-info');
        $I->see('Events (' . count($events) . ')', 'h2');
        $I->see(join(',', $events), '.herbie-info-events');
    }

    public function testNumberAndSortingOfPlugins(AcceptanceTester $I)
    {
        $plugins = [
            'virtual_core_plugin',
            'twig_core',
            'twig_plus',
            'markdown',
            'rest',
            'textile',
            'imagine',
            'dummy',
            'virtual_local_plugin',
            'virtual_app_plugin',
            'virtual_last_plugin',
        ];
        $I->amOnPage('/herbie-info');
        $I->see('Plugins (' . count($plugins) . ')', 'h2');
        $I->see(join(',', $plugins), '.herbie-info-plugins');
    }

    public function testNumberAndSortingOfCommands(AcceptanceTester $I)
    {
        $commands = [
            'herbie\ClearCacheCommand',
            'herbie\sysplugin\dummy\DummyCommand',
            'tests\_data\site\extend\commands\CustomCommand',
            'tests\_data\src\CustomCommand',
            'tests\_data\src\CustomCommand'
        ];
        $I->amOnPage('/herbie-info');
        $I->see('Commands (' . count($commands) . ')', 'h2');
        $I->see(join(',', $commands), '.herbie-info-commands');
    }
}
