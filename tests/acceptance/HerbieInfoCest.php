<?php

namespace tests\acceptance;

use AcceptanceTester;
use Codeception\Util\HttpCode;

final class HerbieInfoCest
{
    public function testPageTitleAndResponseCode(AcceptanceTester $I)
    {
        $I->amOnPage('/herbie-info');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->see('Herbie Info', 'h1');        
    }
    
    public function testNumberAndSortingOfHerbieConstants(AcceptanceTester $I)
    {
        $constants = [
            'HERBIE_API_VERSION',
            'HERBIE_DEBUG',
            'HERBIE_PATH',
            'HERBIE_PATH_MESSAGES',
            'HERBIE_PATH_SYSPLUGINS',
            'HERBIE_REQUEST_ATTRIBUTE_PAGE',
            'HERBIE_REQUEST_ATTRIBUTE_ROUTE',
            'HERBIE_REQUEST_ATTRIBUTE_ROUTE_PARAMS',
            'HERBIE_VERSION',
        ];        
        $I->amOnPage('/herbie-info');
        $I->see('Constants (' . count($constants) . ')', 'h2');
        foreach ($constants as $index => $constant) {
            $I->see(($index + 1) . '. ' . $constant, 'td');
        }        
    }
    
    public function testNumberAndSortingOfHerbieFunctions(AcceptanceTester $I)
    {
        $functions = [
            'herbie\camelize',
            'herbie\defined_classes',
            'herbie\defined_constants',
            'herbie\defined_functions',
            'herbie\explode_list',
            'herbie\get_callable_name',
            'herbie\get_fully_qualified_class_name',
            'herbie\handle_internal_webserver_assets',
            'herbie\load_composer_plugin_configs',
            'herbie\load_php_config',
            'herbie\load_plugin_config',
            'herbie\load_plugin_configs',
            'herbie\normalize_path',
            'herbie\recursive_array_replace',
            'herbie\render_exception',
        ];        
        $I->amOnPage('/herbie-info');
        $I->see('Functions (' . count($functions) . ')', 'h2');
        foreach ($functions as $index => $function) {
            $I->see(($index + 1) . '. ' . $function, 'td');
        }
    }

    public function testNumberAndSortingOfHerbieClasses(AcceptanceTester $I)
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
            'herbie\NullLogger',
            'herbie\Page',
            'herbie\PageFactory',
            'herbie\PageItem',
            'herbie\PageList',
            'herbie\PageRendererMiddleware',
            'herbie\PageResolverMiddleware',
            'herbie\Plugin',
            'herbie\PluginManager',
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
        $I->see('Classes (' . count($classes) . ')', 'h2');
        foreach ($classes as $index => $class) {
            $I->see(($index + 1) . '. ' . $class, 'td');
        }
    }

    public function testNumberAndSortingOfHerbieMiddlewares(AcceptanceTester $I)
    {
        $middlewares = [
            'herbie\ErrorHandlerMiddleware',
            'herbie\sysplugin\dummy\DummySysPlugin->appMiddleware',
            'herbie\ResponseTimeMiddleware',
            'tests\_data\src\CustomHeader',
            'tests\_data\src\CustomHeader',
            'tests\_data\src\CustomHeader',
            'herbie\sysplugin\dummy\DummySysPlugin->routeMiddleware',
            'tests\_data\src\CustomHeader',
            'tests\_data\src\CustomHeader',
            'herbie\HttpBasicAuthMiddleware',
            'herbie\DownloadMiddleware',
            'herbie\PageResolverMiddleware',
            'herbie\PageRendererMiddleware',
        ];
        $I->amOnPage('/herbie-info');
        $I->see('Middlewares (' . count($middlewares) . ')', 'h2');
        foreach ($middlewares as $index => $middleware) {
            $I->see(($index + 1) . '. ' . $middleware, 'td');
        }
    }

    public function testNumberAndSortingOfHerbieConfig(AcceptanceTester $I)
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
            'enabledPlugins',
            'enabledSysPlugins',
            'fileExtensions.layouts',
            'fileExtensions.media.archives',
            'fileExtensions.media.audio',
            'fileExtensions.media.code',
            'fileExtensions.media.documents',
            'fileExtensions.media.images',
            'fileExtensions.media.videos',
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
        $I->see('Config (' . count($configs) . ')', 'h2');
        foreach ($configs as $index => $config) {
            $I->see(($index + 1) . '. ' . $config, 'td');
        }
    }
}
