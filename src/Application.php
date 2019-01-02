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
use Herbie\Exception\SystemException;
use Herbie\Menu\MenuBuilder;
use Herbie\Menu\MenuList;
use Herbie\Menu\MenuTree;
use Herbie\Menu\RootPath;
use Herbie\Middleware\PageRendererMiddleware;
use Herbie\Middleware\ErrorHandlerMiddleware;
use Herbie\Middleware\MiddlewareDispatcher;
use Herbie\Middleware\PageResolverMiddleware;
use Herbie\Persistence\FlatfilePagePersistence;
use Herbie\Persistence\FlatfilePersistenceInterface;
use herbie\plugin\shortcode\classes\Shortcode;
use herbie\plugin\twig\classes\Twig;
use Herbie\Repository\DataRepositoryInterface;
use Herbie\Repository\FlatfilePageRepository;
use Herbie\Repository\PageRepositoryInterface;
use Herbie\Repository\YamlDataRepository;
use Herbie\Url\UrlGenerator;
use Herbie\Url\UrlMatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use Tebe\HttpFactory\HttpFactory;

defined('HERBIE_DEBUG') or define('HERBIE_DEBUG', false);

class Application
{
    /**
     * Herbie version
     * @see http://php.net/version-compare
     * @var string
     */
    const VERSION = '2.0.0';

    /**
     * @var ContainerInterface
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
     * @throws \Exception
     */
    public function __construct($sitePath, $vendorDir = '../vendor')
    {
        $this->sitePath = $this->normalizePath($sitePath);
        $this->vendorDir = $this->normalizePath($vendorDir);
        $this->middlewares = [];
        $this->init();
    }

    /**
     * @param string $path
     * @return string
     * @throws \Exception
     */
    protected function normalizePath(string $path)
    {
        $realpath = realpath($path);
        if ($realpath === false) {
            $message = sprintf('Could not normalize path "%s"', $path);
            throw SystemException::serverError($message);
        }
        return rtrim($realpath, '/');
    }

    /**
     * Initialize the application.
     */
    protected function init()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register($this->sitePath . '/runtime/log');

        $this->container = $c = new Container();

        $c[Environment::class] = $environment = new Environment();

        $c[Config::class] = $config = new Config(
            $this->sitePath,
            dirname($_SERVER['SCRIPT_FILENAME']),
            $environment->getBaseUrl()
        );

        // Add custom PSR-4 plugin path to Composer autoloader
        $pluginsPath = $c[Config::class]->get('plugins.path');
        $autoload = require($this->vendorDir . '/autoload.php');
        $autoload->addPsr4('herbie\\plugin\\', $pluginsPath);

        $c[HttpFactory::class] = new HttpFactory();

        $c[ServerRequestInterface::class] = $c[HttpFactory::class]->createServerRequestFromGlobals();

        $c[Alias::class] = new Alias([
            '@app' => $config->get('app.path'),
            '@asset' => $this->sitePath . '/assets',
            '@media' => $config->get('media.path'),
            '@page' => $config->get('pages.path'),
            '@plugin' => $config->get('plugins.path'),
            '@site' => $this->sitePath,
            '@vendor' => $this->vendorDir,
            '@web' => $config->get('web.path')
        ]);

        $c[SlugGenerator::class] = function ($c) {
            $locale = $c[Config::class]->get('language');
            $options = new SlugOptions([
                'locale' => $locale,
                'delimiter' => '-'
            ]);
            return new SlugGenerator($options);
        };

        $c[Assets::class] = function ($c) {
            return new Assets($c[Alias::class], $c[Config::class]->get('web.url'));
        };

        $c[Cache::class] = function () {
            return new Cache();
        };

        $c[DataRepositoryInterface::class] = function ($c) {
            $dataRepository = new YamlDataRepository(
                $c[Config::class]->get('data.path'),
                $c[Config::class]->get('data.extensions')
            );
            return $dataRepository;
        };

        $c[FlatfilePersistenceInterface::class] = function ($c) {
            return new FlatfilePagePersistence($c[Alias::class]);
        };

        $c[PageRepositoryInterface::class] = function ($c) {
            $pageRepository = new FlatfilePageRepository(
                $c[FlatfilePersistenceInterface::class],
                new PageFactory()
            );
            return $pageRepository;
        };

        $c[MenuBuilder::class] = function ($c) {

            $paths = [];
            $paths['@page'] = $this->normalizePath($c[Config::class]->get('pages.path'));
            foreach ($c[Config::class]->get('pages.extra_paths', []) as $alias) {
                $paths[$alias] = $c[Alias::class]->get($alias);
            }
            $extensions = $c[Config::class]->get('pages.extensions', []);

            $builder = new MenuBuilder(
                $c[FlatfilePersistenceInterface::class],
                $paths,
                $extensions
            );
            return $builder;
        };

        $c[PluginManager::class] = function ($c) {
            $enabled = $c[Config::class]->get('plugins.enable', []);
            $path = $c[Config::class]->get('plugins.path');
            $enabledSysPlugins = $c[Config::class]->get('sysplugins.enable');
            return new PluginManager($enabled, $path, $enabledSysPlugins, $this);
        };

        $c[UrlGenerator::class] = function ($c) {
            return new UrlGenerator(
                $c[ServerRequestInterface::class],
                $c[Environment::class],
                $c[Config::class]->get('nice_urls', false)
            );
        };

        setlocale(LC_ALL, $c[Config::class]->get('locale'));

        // Init PluginManager at first
        if (true === $c[PluginManager::class]->init($c[Config::class])) {
            $c[PluginManager::class]->trigger('pluginsInitialized', $c[PluginManager::class]);
            $c[PluginManager::class]->trigger('shortcodeInitialized', $c[Shortcode::class]);

            $c[MenuList::class] = function ($c) {
                $cache = $c[Cache::class];
                $c[MenuBuilder::class]->setCache($cache);
                return $c[MenuBuilder::class]->buildCollection();
            };

            $c[MenuTree::class] = function ($c) {
                return MenuTree::buildTree($c[MenuList::class]);
            };

            $c[RootPath::class] = function ($c) {
                $rootPath = new RootPath(
                    $c[MenuList::class],
                    $c[Environment::class]->getRoute()
                );
                return $rootPath;
            };

            $c[Translator::class] = function ($c) {
                $translator = new Translator($c[Config::class]->get('language'), [
                    'app' => $c[Alias::class]->get('@app/../messages')
                ]);
                foreach ($c[PluginManager::class]->getLoadedPlugins() as $key => $dir) {
                    $translator->addPath($key, $dir . '/messages');
                }
                $translator->init();
                return $translator;
            };

            $c[UrlMatcher::class] = function ($c) {
                return new UrlMatcher($c[MenuList::class]);
            };
        }
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
        $middlewares = $this->getMiddlewares();
        $dispatcher = new MiddlewareDispatcher($middlewares);
        $request = $this->getService(ServerRequestInterface::class);
        $response = $dispatcher->dispatch($request);

        $this->getPluginManager()->trigger('outputGenerated', $response);

        $this->emitResponse($response);

        $this->getPluginManager()->trigger('outputRendered');
    }

    /**
     * @return array
     */
    public function getMiddlewares(): array
    {
        $middlewares = array_merge(
            [
                ErrorHandlerMiddleware::class
            ],
            $this->middlewares,
            [
                new PageResolverMiddleware(
                    $this,
                    $this->getEnvironment(),
                    $this->getUrlMatcher(),
                    $this->getPageRepository()
                ),
                new PageRendererMiddleware(
                    $this->getPageCache(),
                    $this->getEnvironment(),
                    $this->getHttpFactory(),
                    $this->getPluginManager()
                )
            ]
        );
        return $middlewares;
    }

    /**
     * @param array $middlewares
     * @return Application
     */
    public function setMiddlewares(array $middlewares)
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    /**
     * @return Environment
     */
    public function getEnvironment()
    {
        return $this->getService(Environment::class);
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
     * @return UrlMatcher
     */
    public function getUrlMatcher()
    {
        return $this->getService(UrlMatcher::class);
    }

    /**
     * @return PageRepositoryInterface
     */
    public function getPageRepository()
    {
        return $this->getService(PageRepositoryInterface::class);
    }

    /**
     * @return PluginManager
     */
    public function getPluginManager()
    {
        return $this->getService(PluginManager::class);
    }

    /**
     * @param ResponseInterface $response
     */
    protected function emitResponse(ResponseInterface $response): void
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

    public function test()
    {
        $mappings = FlatfilePagePersistence::getRouteToIdMapping(
            $this->getConfig()->get('pages.path'),
            $this->getConfig()->get('pages.extensions')
        );

        $repository = $this->getPageRepository();

        echo "<pre>";

        foreach ($mappings as $path => $route) {
            $page = $repository->find($path);
            print_r($page);
        }

        echo "</pre>";
        exit;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->getService(Config::class);
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->getService(Environment::class)->getRoute();
    }

    /**
     * @return string
     */
    public function getRouteLine()
    {
        return $this->getService(Environment::class)->getRouteLine();
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->getService(Environment::class)->getBasePath();
    }

    /**
     * @param Twig $twig
     * @return Application
     */
    public function setTwig(Twig $twig)
    {
        $this->setService(Twig::class, $twig);
        return $this;
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
     * @param Shortcode $shortcode
     * @return Application
     */
    public function setShortcode(Shortcode $shortcode)
    {
        $this->setService(Shortcode::class, $shortcode);
        return $this;
    }

    /**
     * @return Alias
     */
    public function getAlias()
    {
        return $this->getService(Alias::class);
    }

    /**
     * @return UrlGenerator
     */
    public function getUrlGenerator()
    {
        return $this->getService(UrlGenerator::class);
    }

    /**
     * @return CacheInterface
     */
    public function getPageCache()
    {
        return $this->getService(Cache::class);
    }

    /**
     * @param CacheInterface $cache
     * @return Application
     */
    public function setPageCache(CacheInterface $cache)
    {
        $this->setService(Cache::class, $cache);
        return $this;
    }

    /**
     * @return Assets
     */
    public function getAssets()
    {
        return $this->getService(Assets::class);
    }

    /**
     * @return RootPath
     */
    public function getMenuRootPath()
    {
        return $this->getService(RootPath::class);
    }

    /**
     * @return MenuTree
     */
    public function getMenuNode()
    {
        return $this->getService(MenuTree::class);
    }

    /**
     * @return Twig
     */
    public function getTwig()
    {
        $twig = $this->getService(Twig::class);
        return $twig;
    }

    /**
     * @return HttpFactory
     */
    public function getHttpFactory()
    {
        return $this->getService(HttpFactory::class);
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest()
    {
        return $this->getService(ServerRequestInterface::class);
    }

    /**
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->getService(Translator::class);
    }

    /**
     * @return MenuList
     */
    public function getMenuList()
    {
        return $this->getService(MenuList::class);
    }

    /**
     * @return DataRepositoryInterface
     */
    public function getDataRepository()
    {
        return $this->getService(DataRepositoryInterface::class);
    }

    public function getSlugGenerator()
    {
        return $this->getService(SlugGenerator::class);
    }

    /**
     * @return string
     */
    public function getSitePath()
    {
        return $this->sitePath;
    }

    /**
     * @return string
     */
    public function getVendorPath()
    {
        return $this->vendorDir;
    }
}
