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
     * @var DI
     */
    protected static $DI;

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

        static::$DI = $DI = DI::instance();

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

        $DI['Assets'] = function ($DI) {
            return new Assets($DI['Alias'], $DI['Config']->get('web.url'));
        };

        $DI['Cache\PageCache'] = function ($DI) {
            return Cache\CacheFactory::create('page', $DI['Config']);
        };

        $DI['Cache\DataCache'] = function ($DI) {
            return Cache\CacheFactory::create('data', $DI['Config']);
        };

        $DI['DataArray'] = function ($DI) {
            $loader = new Loader\DataLoader($DI['Config']->get('data.extensions'));
            return $loader->load($DI['Config']->get('data.path'));
        };

        $DI['Loader\PageLoader'] = function ($DI) {
            $loader = new Loader\PageLoader($DI['Alias']);
            return $loader;
        };

        $DI['Menu\Page\Builder'] = function ($DI) {

            $paths = [];
            $paths['@page'] = realpath($DI['Config']->get('pages.path'));
            foreach ($DI['Config']->get('pages.extra_paths', []) as $alias) {
                $paths[$alias] = $DI['Alias']->get($alias);
            }
            $extensions = $DI['Config']->get('pages.extensions', []);

            $builder = new Menu\Page\Builder($paths, $extensions);
            return $builder;
        };

        $DI['PluginManager'] = function () {
            return new PluginManager();
        };

        $DI['Url\UrlGenerator'] = function ($DI) {
            return new Url\UrlGenerator($DI['Request'], $DI['Config']->get('nice_urls', false));
        };

        setlocale(LC_ALL, $DI['Config']->get('locale'));

        // Add custom PSR-4 plugin path to Composer autoloader
        $autoload = require($this->vendorDir . '/autoload.php');
        $autoload->addPsr4('herbie\\sysplugin\\', __DIR__ . '/../plugins/');
        $autoload->addPsr4('herbie\\plugin\\', $DI['Config']->get('plugins.path'));

        // Init PluginManager at first
        if (true === $DI['PluginManager']->init($DI['Config'])) {

            $this->fireEvent('onPluginsInitialized', $DI['PluginManager']);

            $this->fireEvent('onShortcodeInitialized', $DI['PluginManager']->getPlugin('shortcode'));

            $DI['Menu\Page\Collection'] = function ($DI) {
                $DI['Menu\Page\Builder']->setCache($DI['Cache\DataCache']);
                return $DI['Menu\Page\Builder']->buildCollection();
            };

            $DI['Menu\Page\Node'] = function ($DI) {
                return Menu\Page\Node::buildTree($DI['Menu\Page\Collection']);
            };

            $DI['Menu\Page\RootPath'] = function ($DI) {
                $rootPath = new Menu\Page\RootPath($DI['Menu\Page\Collection'], $DI['Request']->getRoute());
                return $rootPath;
            };

            $DI['Menu\Post\Collection'] = function ($DI) {
                $builder = new Menu\Post\Builder($DI['Cache\DataCache'], $DI['Config']);
                return $builder->build();
            };

            $DI['Page'] = function ($DI) {

                try {

                    $route = $DI['Request']->getRoute();
                    $menuItem = $DI['Url\UrlMatcher']->match($route);
                    $path = $menuItem->getPath();

                    $page = false;

                    // @todo Implement a proper page cache
                    // get content from cache if cache enabled
                    if (empty($menuItem->nocache)) {
                        $page = $DI['Cache\PageCache']->get($path);
                    }

                    if (false === $page) {

                        $page = new Page();
                        $page->setLoader($DI['Loader\PageLoader']);
                        $page->load($path);

                        Application::fireEvent('onPageLoaded', $page);

                        if (empty($menuItem->nocache)) {
                            $DI['Cache\PageCache']->set($path, $page);
                        }
                    }

                } catch (\Exception $e) {

                    $page = new Page();
                    $page->layout = 'error.html';
                    $page->setError($e);
                }

                return $page;

            };

            $DI['Translator'] = function ($DI) {
                $translator = new Translator($DI['Config']->get('language'), ['app' => $DI['Alias']->get('@app/../messages')]);
                foreach ($DI['PluginManager']->getDirectories() as $key => $dir) {
                    $translator->addPath($key, $dir . '/messages');
                }
                $translator->init();
                return $translator;
            };

            $DI['Url\UrlMatcher'] = function ($DI) {
                return new Url\UrlMatcher($DI['Menu\Page\Collection'], $DI['Menu\Post\Collection']);
            };

        }

    }

    /**
     * Fire an event.
     * @param  string $eventName
     * @param  array $attributes
     * @return mixed
     */
    public static function fireEvent($eventName, $subject = null, array $attributes = [])
    {
        return static::$DI['PluginManager']->dispatch($eventName, $subject, $attributes, static::$DI);
    }

    /**
     * Retrieve a registered service from DI container.
     * @param string $service
     * @return mixed
     */
    public static function getService($service)
    {
        return static::$DI[$service];
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

        if (0 < static::$DI['Config']->get('display_load_time', 0)) {
            $time = Benchmark::mark();
            echo sprintf("\n<!-- Generated by Herbie in %s seconds | www.getherbie.org -->", $time);
        }
    }

    public function renderPage(Page $page)
    {

        $content = new \stdClass();
        $content->string = '';

        try {

            if (empty($page->layout)) {
                $content = $page->getSegment(0);
                static::fireEvent('onRenderContent', $content, $page->getData());
            } else {
                static::fireEvent('onRenderLayout', $content, ['layout' => $page->layout]);
            }

        } catch (\Exception $e) {

            $page->setError($e);
            static::fireEvent('onRenderLayout', $content, ['layout' => 'error.html']);

        }

        $response = new Http\Response($content->string);
        $response->setStatus($page->getStatusCode());
        $response->setHeader('Content-Type', $page->content_type);

        return $response;
    }

}
