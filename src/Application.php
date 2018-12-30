<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

use Ausi\SlugGenerator\SlugGenerator;
use Ausi\SlugGenerator\SlugOptions;
use Herbie\Cache\PageCache;
use Herbie\Loader\PageLoader;
use Herbie\Menu\Page\Node;
use Herbie\Menu\Page\RootPath;
use Herbie\Middleware\DispatchMiddleware;
use Herbie\Middleware\ErrorHandlerMiddleware;
use Herbie\Middleware\MiddlewareDispatcher;
use Herbie\Middleware\PageResolverMiddleware;
use herbie\plugin\shortcode\ShortcodePlugin;
use herbie\plugin\twig\classes\Twig;
use Herbie\Url\UrlGenerator;
use Herbie\Url\UrlMatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tebe\HttpFactory\HttpFactory;

defined('HERBIE_DEBUG') or define('HERBIE_DEBUG', false);

class Application
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Page
     */
    protected $page;

    /**
     * @var string
     */
    protected $sitePath;

    /**
     * @var string
     */
    protected $vendorDir;

    /**
     * @var array
     */
    protected $middlewares;

    /**
     * @param string $sitePath
     * @param string $vendorDir
     */
    public function __construct($sitePath, $vendorDir = '../vendor')
    {
        $this->sitePath = realpath($sitePath);
        $this->vendorDir = realpath($vendorDir);
        $this->middlewares = [];
        $this->init();
    }

    /**
     * @param array $middlewares
     * @return Application
     */
    public function setMiddleware(array $middlewares)
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    protected function getMiddleware(): array
    {
        $middlewares = array_merge(
            [
                ErrorHandlerMiddleware::class
            ],
            $this->middlewares,
            [
                new PageResolverMiddleware($this),
                new DispatchMiddleware($this)
            ]
        );
        return $middlewares;
    }

    /**
     * Initialize the application.
     */
    private function init()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register($this->sitePath . '/log');

        $this->container = $container = new Container();

        $container['HttpFactory'] = $httpFactory = new HttpFactory();

        $container['Request'] = $request = $httpFactory->createServerRequestFromGlobals();

        $container['Environment'] = $environment = new Environment($request);

        $container['Config'] = $config = new Config(
            $this->sitePath,
            dirname($_SERVER['SCRIPT_FILENAME']),
            $environment->getBaseUrl()
        );

        // Add custom PSR-4 plugin path to Composer autoloader
        $autoload = require($this->vendorDir . '/autoload.php');
        $autoload->addPsr4('herbie\\plugin\\', $container['Config']->get('plugins.path'));

        $container['Alias'] = new Alias([
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

        $container['SlugGenerator'] = function ($container) {
            $locale = $container['Config']->get('language');
            $options = new SlugOptions([
                'locale' => $locale,
                'delimiter' => '-'
            ]);
            return new SlugGenerator($options);
        };

        $container['Assets'] = function ($container) {
            return new Assets($container['Alias'], $container['Config']->get('web.url'));
        };

        $container['Cache\PageCache'] = function ($container) {
            return Cache\CacheFactory::create('page', $container['Config']);
        };

        $container['Cache\DataCache'] = function ($container) {
            return Cache\CacheFactory::create('data', $container['Config']);
        };

        $container['DataArray'] = function ($container) {
            $loader = new Loader\DataLoader($container['Config']->get('data.extensions'));
            return $loader->load($container['Config']->get('data.path'));
        };

        $container['Loader\PageLoader'] = function ($container) {
            $loader = new Loader\PageLoader($container['Alias']);
            return $loader;
        };

        $container['Menu\Page\Builder'] = function ($container) {

            $paths = [];
            $paths['@page'] = realpath($container['Config']->get('pages.path'));
            foreach ($container['Config']->get('pages.extra_paths', []) as $alias) {
                $paths[$alias] = $container['Alias']->get($alias);
            }
            $extensions = $container['Config']->get('pages.extensions', []);

            $builder = new Menu\Page\Builder($paths, $extensions);
            return $builder;
        };

        $container['PluginManager'] = function ($container) {
            $enabled = $container['Config']->get('plugins.enable', []);
            $path = $container['Config']->get('plugins.path');
            $enabledSysPlugins = $container['Config']->get('sysplugins.enable');
            return new PluginManager($enabled, $path, $enabledSysPlugins, $this);
        };

        $container['Url\UrlGenerator'] = function ($container) {
            return new Url\UrlGenerator(
                $container['Request'],
                $container['Environment'],
                $container['Config']->get('nice_urls', false)
            );
        };

        setlocale(LC_ALL, $container['Config']->get('locale'));

        // Init PluginManager at first
        if (true === $container['PluginManager']->init($container['Config'])) {

            $container['PluginManager']->trigger('pluginsInitialized', $container['PluginManager']);
            $container['PluginManager']->trigger('shortcodeInitialized', $container['Shortcode']);

            $container['Menu\Page\Collection'] = function ($container) {
                $container['Menu\Page\Builder']->setCache($container['Cache\DataCache']);
                return $container['Menu\Page\Builder']->buildCollection();
            };

            $container['Menu\Page\Node'] = function ($container) {
                return Menu\Page\Node::buildTree($container['Menu\Page\Collection']);
            };

            $container['Menu\Page\RootPath'] = function ($container) {
                $rootPath = new Menu\Page\RootPath($container['Menu\Page\Collection'], $container['Environment']->getRoute());
                return $rootPath;
            };

            $container['Menu\Post\Collection'] = function ($container) {
                $builder = new Menu\Post\Builder($container['Cache\DataCache'], $container['Config']);
                return $builder->build();
            };

            $container['Translator'] = function ($container) {
                $translator = new Translator($container['Config']->get('language'), [
                    'app' => $container['Alias']->get('@app/../messages')
                ]);
                foreach ($container['PluginManager']->getLoadedPlugins() as $key => $dir) {
                    $translator->addPath($key, $dir . '/messages');
                }
                $translator->init();
                return $translator;
            };

            $container['Url\UrlMatcher'] = function ($container) {
                return new Url\UrlMatcher($container['Menu\Page\Collection'], $container['Menu\Post\Collection']);
            };
        }
    }

    /**
     * Retrieve a registered service from DI container.
     * @param string $name
     * @return mixed
     */
    protected function getService($name)
    {
        return $this->container[$name];
    }

    /**
     * @param string $name
     * @param mixed $service
     * @return Application
     */
    protected function setService($name, $service)
    {
        $this->container[$name] = $service;
        return $this;
    }

    /**
     * Get the loaded (current) Page from container. This is a shortcut to Application::getService('Page').
     * @return Page
     */

    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param Page $page
     * @return Application
     */
    public function setPage(Page $page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        $middlewares = $this->getMiddleware();
        $dispatcher = new MiddlewareDispatcher($middlewares);
        $request = $this->getService('Request');
        $response = $dispatcher->dispatch($request);

        $this->getPluginManager()->trigger('outputGenerated', $response);

        $this->emitResponse($response);

        $this->getPluginManager()->trigger('outputRendered');
    }

    /**
     * @param ResponseInterface $response
     */
    private function emitResponse(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();
        http_response_code($statusCode);
        foreach ($response->getHeaders() as $k => $values) {
            foreach ($values as $v) {
                header(sprintf('%s: %s', $k, $v), false);
            }
        }
        echo $response->getBody();
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->getService('Environment')->getRoute();
    }

    /**
     * @return string
     */
    public function getRouteLine()
    {
        return $this->getService('Environment')->getRouteLine();
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->getService('Environment')->getBasePath();
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->getService('Config');
    }

    /**
     * @param Twig $twig
     * @return Application
     */
    public function setTwig($twig)
    {
        $this->setService('Twig', $twig);
        return $this;
    }

    /**
     * @return PluginManager
     */
    public function getPluginManager()
    {
        return $this->getService('PluginManager');
    }

    /**
     * @param ShortcodePlugin $shortcode
     * @return Application
     */
    public function setShortcode($shortcode)
    {
        $this->setService('Shortcode', $shortcode);
        return $this;
    }

    /**
     * @return Alias
     */
    public function getAlias()
    {
        return $this->getService('Alias');
    }

    /**
     * @return UrlGenerator
     */
    public function getUrlGenerator()
    {
        return $this->getService('Url\UrlGenerator');
    }

    /**
     * @return Environment
     */
    public function getEnvironment()
    {
        return $this->getService('Environment');
    }

    /**
     * @return PageCache
     */
    public function getPageCache()
    {
        return $this->getService('Cache\PageCache');
    }

    /**
     * @return Assets
     */
    public function getAssets()
    {
        return $this->getService('Assets');
    }

    /**
     * @return RootPath
     */
    public function getMenuPageRootPath()
    {
        return $this->getService('Menu\Page\RootPath');
    }

    /**
     * @return Node
     */
    public function getMenuPageNode()
    {
        return $this->getService('Menu\Page\Node');
    }

    /**
     * @return Twig
     */
    public function getTwig()
    {
        $twig = $this->getService('Twig');
        return $twig;
    }

    /**
     * @return HttpFactory
     */
    public function getHttpFactory()
    {
        return $this->getService('HttpFactory');
    }

    /**
     * @return UrlMatcher
     */
    public function getUrlMatcher()
    {
        return $this->getService('Url\UrlMatcher');
    }

    /**
     * @return PageLoader
     */
    public function getPageLoader()
    {
        return $this->getService('Loader\PageLoader');
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->getService('Request');
    }

    /**
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->getService('Translator');
    }

    /**
     * @return \Herbie\Menu\Page\Collection
     */
    public function getMenuPageCollection()
    {
        return $this->getService('Menu\Page\Collection');
    }

    /**
     * @return \Herbie\Menu\Post\Collection
     */
    public function getMenuPostCollection()
    {
        return $this->getService('Menu\Post\Collection');
    }
}
