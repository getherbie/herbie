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
}
