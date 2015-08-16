<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

defined('HERBIE_DEBUG') or define('HERBIE_DEBUG', false);

class Application
{
    /**
     * @var Container
     */
    protected static $container;

    /**
     * @var Page
     */
    protected static $page;

    /**
     * @var string
     */
    protected $sitePath;

    /**
     * @var string
     */
    protected $vendorDir;

    /**
     * @param string $sitePath
     * @param string $vendorDir
     */
    public function __construct($sitePath, $vendorDir = '../vendor')
    {
        Benchmark::mark();
        $this->sitePath = realpath($sitePath);
        $this->vendorDir = realpath($vendorDir);
        $this->init();
    }

    /**
     * Initialize the application.
     */
    private function init()
    {

        $errorHandler = new ErrorHandler();
        $errorHandler->register();

        static::$container = $DI = DI::instance();

        $DI['Request'] = $request = new Http\Request();
        $DI['Config'] = $config = new Config($this->sitePath, dirname($_SERVER['SCRIPT_FILENAME']), $request->getBaseUrl());

        $DI['Alias'] = new Alias([
            '@app' => $config->get('app.path'),
            '@asset' => $this->sitePath . '/assets',
            '@media' => $config->get('media.path'),
            '@page' => $config->get('pages.path'),
            '@plugin' => $config->get('plugins.path'),
            '@post' => $config->get('posts.path'),
            '@site' => $this->sitePath,
            '@vendor' => $this->vendorDir,
            '@web' => $config->get('web.path')
        ]);

        $DI['Assets'] = function ($c) {
            return new Assets($c['Alias'], $c['Config']->get('web.url'));
        };

        $DI['Cache\PageCache'] = function ($c) {
            return Cache\CacheFactory::create('page', $c['Config']);
        };

        $DI['Cache\DataCache'] = function ($c) {
            return Cache\CacheFactory::create('data', $c['Config']);
        };

        $DI['DataArray'] = function ($c) {
            $loader = new Loader\DataLoader($c['Config']->get('data.extensions'));
            return $loader->load($c['Config']->get('data.path'));
        };

        $DI['EventDispatcher'] = function () {
            return new EventDispatcher();
        };

        $DI['Loader\PageLoader'] = function ($c) {
            $loader = new Loader\PageLoader($c['Alias']);
            return $loader;
        };

        $DI['Menu\Page\Builder'] = function ($c) {

            $paths = [];
            $paths['@page'] = realpath($c['Config']->get('pages.path'));
            foreach ($c['Config']->get('pages.extra_paths', []) as $alias) {
                $paths[$alias] = $c['Alias']->get($alias);
            }
            $extensions = $c['Config']->get('pages.extensions', []);

            $builder = new Menu\Page\Builder($paths, $extensions);
            return $builder;
        };

        $DI['Menu\Page\Collection'] = function ($c) {
            $c['Menu\Page\Builder']->setCache($c['Cache\DataCache']);
            return $c['Menu\Page\Builder']->buildCollection();
        };

        $DI['Menu\Page\Node'] = function ($c) {
            return Menu\Page\Node::buildTree($c['Menu\Page\Collection']);
        };

        $DI['Menu\Page\RootPath'] = function ($c) {
            $rootPath = new Menu\Page\RootPath($c['Menu\Page\Collection'], $c['Request']->getRoute());
            return $rootPath;
        };

        $DI['Menu\Post\Collection'] = function ($c) {
            $builder = new Menu\Post\Builder($c['Cache\DataCache'], $c['Config']);
            return $builder->build();
        };

        $DI['Page'] = function ($c) {

            try {

                $route = $c['Request']->getRoute();
                $menuItem = $c['Url\UrlMatcher']->match($route);
                $path = $menuItem->getPath();

                $page = false;

                // @todo Implement a proper page cache
                // get content from cache if cache enabled
                if (empty($menuItem->nocache)) {
                    $page = $c['Cache\PageCache']->get($path);
                }

                if (false === $page) {

                    $page = new Page();
                    $page->setLoader($c['Loader\PageLoader']);
                    $page->load($path);

                    Application::fireEvent('onPageLoaded', $page);

                    if (empty($menuItem->nocache)) {
                        $c['Cache\PageCache']->set($path, $page);
                    }
                }

            } catch (\Exception $e) {

                $page = new Page();
                $page->layout = 'error.html';
                $page->setError($e);
            }

            return $page;

        };

        $DI['PluginManager'] = function () {
            return new PluginManager();
        };

        $DI['Translator'] = function ($c) {
            if (!$c['PluginManager']->isInitialized()) {
                throw new \Exception('You have to initialize PluginManager before using Translator.');
            }
            $translator = new Translator($c['Config']->get('language'), ['app' => $c['Alias']->get('@app/../messages')]);
            foreach ($c['PluginManager']->getDirectories() as $key => $dir) {
                $translator->addPath($key, $dir . '/messages');
            }
            $translator->init();
            return $translator;
        };

        $DI['Url\UrlGenerator'] = function ($c) {
            return new Url\UrlGenerator($c['Request'], $c['Config']->get('nice_urls', false));
        };

        $DI['Url\UrlMatcher'] = function ($c) {
            return new Url\UrlMatcher($c['Menu\Page\Collection'], $c['Menu\Post\Collection']);
        };

        setlocale(LC_ALL, $DI['Config']->get('locale'));

        // Add custom PSR-4 plugin path to Composer autoloader
        $autoload = require($this->vendorDir . '/autoload.php');
        $autoload->addPsr4('herbie\\sysplugin\\', __DIR__ . '/../plugins/');
        $autoload->addPsr4('herbie\\plugin\\', $DI['Config']->get('plugins.path'));

        $DI['PluginManager']->init($DI['Config']);

        #echo"<pre>";print_r($DI['PluginManager']->getListeners());echo"</pre>";

        $this->fireEvent('onPluginsInitialized', $DI['PluginManager']);

        #foreach ($DI['PluginManager']->getPlugins() as $key => $plugin) {
        #    echo $key . ' --> ' . get_class($plugin) . '<br>';
        #}
        #exit;

        $this->fireEvent('onShortcodeInitialized', $DI['PluginManager']->getPlugin('shortcode'));

    }

    /**
     * Fire an event.
     * @param  string $eventName
     * @param  array $attributes
     * @return mixed
     */
    public static function fireEvent($eventName, $subject = null, array $attributes = [])
    {
        return static::$container['PluginManager']->dispatch($eventName, $subject, $attributes);
    }

    /**
     * Retrieve a registered service from DI container.
     * @param string $service
     * @return mixed
     */
    public static function getService($service)
    {
        return static::$container[$service];
    }

    /**
     * Get the loaded (current) Page from DI container. This is a shortcut to Application::getService('Page').
     * @return Page
     */
    public static function getPage()
    {
        if (null === static::$page) {
            static::$page = static::getService('Page');
        }
        return static::$page;
    }

    /**
     * @return void
     */
    public function run()
    {
        $page = $this->getPage();

        $response = $this->renderPage($page);

        $this->fireEvent('onOutputGenerated', ['response' => $response]);

        $response->send();

        $this->fireEvent('onOutputRendered');

        if (0 < static::$container['Config']->get('display_load_time', 0)) {
            $time = Benchmark::mark();
            echo sprintf("\n<!-- Generated by Herbie in %s seconds | www.getherbie.org -->", $time);
        }
    }

    public function renderPage(Page $page)
    {

        $content = '';

        try {

            if (empty($page->layout)) {
                static::fireEvent('onRenderPageSegment', null, ['content' => &$content, 'segment' => 0, 'page' => $page]);
            } else {
                static::fireEvent('onRenderLayout', null, ['content' => &$content, 'layout' => $page->layout]);
            }

        } catch (\Exception $e) {

            $page->setError($e);
            static::fireEvent('onRenderLayout', null, ['content' => &$content, 'layout' => 'error.html']);

        }

        $response = new Http\Response($content);
        $response->setStatus($page->getStatusCode());
        $response->setHeader('Content-Type', $page->content_type);

        return $response;
    }

}
