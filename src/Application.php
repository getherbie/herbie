<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie;

use Ausi\SlugGenerator\SlugGenerator;
use Ausi\SlugGenerator\SlugGeneratorInterface;
use Ausi\SlugGenerator\SlugOptions;
use Herbie\Exception\SystemException;
use Herbie\Menu\MenuBuilder;
use Herbie\Menu\MenuFactory;
use Herbie\Menu\MenuList;
use Herbie\Menu\MenuTrail;
use Herbie\Menu\MenuTree;
use Herbie\Middleware\DownloadMiddleware;
use Herbie\Middleware\ErrorHandlerMiddleware;
use Herbie\Middleware\MiddlewareDispatcher;
use Herbie\Middleware\PageRendererMiddleware;
use Herbie\Middleware\PageResolverMiddleware;
use Herbie\Persistence\FlatfilePagePersistence;
use Herbie\Persistence\FlatfilePersistenceInterface;
use Herbie\Repository\DataRepositoryInterface;
use Herbie\Repository\FlatfilePageRepository;
use Herbie\Repository\PageRepositoryInterface;
use Herbie\Repository\YamlDataRepository;
use Herbie\Url\UrlGenerator;
use Herbie\Url\UrlMatcher;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use Tebe\HttpFactory\HttpFactory;

defined('HERBIE_DEBUG') or define('HERBIE_DEBUG', false);

define('HERBIE', [
    'DEBUG' => true,
    'ROOT' => __DIR__
]);

print_r(HERBIE['DEBUG']);

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
    private $container;

    /**
     * @var string
     */
    private $sitePath;

    /**
     * @var string
     */
    private $vendorDir;

    /**
     * @var array
     */
    private $middlewares;

    /**
     * @param string $sitePath
     * @param string $vendorDir
     * @throws \Exception
     */
    public function __construct($sitePath, $vendorDir = '../vendor')
    {
        $this->sitePath = normalize_path($sitePath);
        $this->vendorDir = normalize_path($vendorDir);
        $this->middlewares = [];
        $this->init();
    }

    /**
     * Initialize the application.
     * @throws SystemException
     */
    private function init()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->register($this->sitePath . '/runtime/log');
        $this->container = $this->initContainer();

        setlocale(LC_ALL, $this->container->get(Config::class)->get('locale'));

        // Add custom PSR-4 plugin path to Composer autoloader
        $pluginsPath = $this->container->get(Config::class)->paths->plugins;
        $autoload = require($this->vendorDir . '/autoload.php');
        $autoload->addPsr4('herbie\\plugin\\', $pluginsPath);
    }

    /**
     * Initializes and returns container
     * @return Container
     */
    private function initContainer(): Container
    {
        $c = new Container();

        $c[Environment::class] = function () {
            return new Environment();
        };

        $c[Config::class] = function (Container $c) {

            $APP_PATH = rtrim(__DIR__, '/');
            $SITE_PATH = rtrim($this->sitePath, '/');
            $WEB_PATH = rtrim(preg_replace('#\/?index.php#', '', dirname($_SERVER['SCRIPT_FILENAME'])), '/');
            $WEB_URL = rtrim($c->get(Environment::class)->getBaseUrl(), '/');

            $consts = [
                'APP_PATH' => $APP_PATH,
                'WEB_PATH' => $WEB_PATH,
                'WEB_URL' => $WEB_URL,
                'SITE_PATH' => $SITE_PATH
            ];

            // config default
            $defaults = require(__DIR__ . '/../config/test.php');
            $config = new Config($defaults);

            // config user
            if (is_file($this->sitePath . '/config/test.php')) {
                $array = require($this->sitePath . '/config/test.php');
            } elseif (is_file($this->sitePath . '/config/test.yml')) {
                $content = file_get_contents($this->sitePath . '/config/test.yml');
                $content = str_replace(array_keys($consts), array_values($consts), $content);
                $array = Yaml::parse($content);
            }
            $userConfig = new Config($array);

            // config plugins
            $array = [];
            $dir = $userConfig->paths->plugins ?? $config->paths->plugins;
            if (is_readable($dir)) {
                $files = scandir($dir);
                foreach ($files as $file) {
                    if (substr($file, 0, 1) === '.') {
                        continue;
                    }
                    $configFile = $dir . '/' . $file . '/config.yml';
                    if (is_file($configFile)) {
                        $content = file_get_contents($configFile);
                        $content = str_replace(array_keys($consts), array_values($consts), $content);
                        $array['plugins'][$file] = Yaml::parse($content);
                    } else {
                        $array['plugins'][$file] = [];
                    }
                }
            }
            $config->merge(new Config($array));

            $config->merge($userConfig);

            return $config;
        };

        $c[HttpFactory::class] = function () {
            return new HttpFactory();
        };

        $c[ServerRequestInterface::class] = function (Container $c) {
            return $c[HttpFactory::class]->createServerRequestFromGlobals();
        };

        $c[Alias::class] = function (Container $c) {
            $config = $c->get(Config::class);
            return new Alias([
                '@app' => $config->paths->app,
                '@asset' => $this->sitePath . '/assets',
                '@media' => $config->paths->media,
                '@page' => $config->paths->pages,
                '@plugin' => $config->paths->plugins,
                '@site' => $this->sitePath,
                '@vendor' => $this->vendorDir,
                '@web' => $config->paths->web,
                '@widget' => $config->paths->app . '/../templates/widgets'
            ]);
        };

        $c[TwigRenderer::class] = function (Container $c) {
            $twig = new TwigRenderer(
                $c[Alias::class],
                $c[Config::class],
                $c[UrlGenerator::class],
                $c[SlugGeneratorInterface::class],
                $c[Assets::class],
                $c[MenuList::class],
                $c[MenuTree::class],
                $c[MenuTrail::class],
                $c[Environment::class],
                $c[DataRepositoryInterface::class],
                $c[Translator::class],
                $c[EventManager::class]
            );
            return $twig;
        };

        $c[SlugGeneratorInterface::class] = function (Container $c) {
            $locale = $c[Config::class]->get('language');
            $options = new SlugOptions([
                'locale' => $locale,
                'delimiter' => '-'
            ]);
            return new SlugGenerator($options);
        };

        $c[Assets::class] = function (Container $c) {
            return new Assets($c[Alias::class], $c[Environment::class]);
        };

        $c[Cache::class] = function () {
            return new Cache();
        };

        $c[DataRepositoryInterface::class] = function (Container $c) {
            $dataRepository = new YamlDataRepository($c[Config::class]);
            return $dataRepository;
        };

        $c[FlatfilePersistenceInterface::class] = function (Container $c) {
            return new FlatfilePagePersistence($c[Alias::class]);
        };

        $c[PageRepositoryInterface::class] = function (Container $c) {
            $pageRepository = new FlatfilePageRepository(
                $c[FlatfilePersistenceInterface::class],
                new PageFactory()
            );
            return $pageRepository;
        };

        $c[MenuBuilder::class] = function (Container $c) {
            $builder = new MenuBuilder(
                $c[FlatfilePersistenceInterface::class],
                $c[Config::class],
                new MenuFactory()
            );
            return $builder;
        };

        $c[EventManager::class] = function () {
            $zendEventManager = new \Zend\EventManager\EventManager();
            $zendEventManager->setEventPrototype(new Event());
            return new EventManager($zendEventManager);
        };

        $c[PluginManager::class] = function (Container $c) {
            return new PluginManager($c[EventManager::class], $c[Config::class], $c);
        };

        $c[UrlGenerator::class] = function (Container $c) {
            return new UrlGenerator(
                $c[ServerRequestInterface::class],
                $c[Environment::class],
                $c[Config::class]
            );
        };

        $c[MenuList::class] = function (Container $c) {
            $cache = $c[Cache::class];
            $c[MenuBuilder::class]->setCache($cache);
            return $c[MenuBuilder::class]->buildMenuList();
        };

        $c[MenuTree::class] = function (Container $c) {
            return MenuTree::buildTree($c[MenuList::class]);
        };

        $c[MenuTrail::class] = function (Container $c) {
            return new MenuTrail($c[MenuList::class], $c[Environment::class]);
        };

        $c[Translator::class] = function (Container $c) {
            $translator = new Translator($c[Config::class]->language);
            $translator->addPath('app', $c[Config::class]->paths->messages);
            foreach ($c[PluginManager::class]->getPluginPaths() as $key => $dir) {
                $translator->addPath($key, $dir . '/messages');
            }
            return $translator;
        };

        $c[UrlMatcher::class] = function (Container $c) {
            return new UrlMatcher($c[MenuList::class]);
        };

        $c[MiddlewareDispatcher::class] = function (Container $c) {
            $middlewares = array_merge(
                [
                    new ErrorHandlerMiddleware(
                        $c->get(TwigRenderer::class)
                    )
                ],
                $this->middlewares,
                [
                    new DownloadMiddleware(
                        $c->get(Config::class),
                        $c->get(Alias::class)
                    ),
                    new PageResolverMiddleware(
                        $this,
                        $c->get(Environment::class),
                        $c->get(PageRepositoryInterface::class),
                        $c->get(UrlMatcher::class)
                    )
                ],
                $c->get(PluginManager::class)->getMiddlewares(),
                [
                    new PageRendererMiddleware(
                        $c->get(Cache::class),
                        $c->get(Environment::class),
                        $c->get(HttpFactory::class),
                        $c->get(EventManager::class),
                        $c->get(TwigRenderer::class),
                        $c->get(Config::class),
                        $c->get(DataRepositoryInterface::class),
                        $c->get(MenuList::class),
                        $c->get(MenuTree::class),
                        $c->get(MenuTrail::class)
                    )
                ]
            );
            return new MiddlewareDispatcher($middlewares);
        };

        return $c;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function run()
    {
        // Init PluginManager
        $this->getPluginManager()->init();

        // Init Translator
        $this->getTranslator()->init();

        $dispatcher = $this->container->get(MiddlewareDispatcher::class);
        $request = $this->container->get(ServerRequestInterface::class);
        $response = $dispatcher->dispatch($request);

        $this->getEventManager()->trigger('onResponseGenerated', $response);

        $this->emitResponse($response);

        $this->getEventManager()->trigger('onResponseRendered');
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
        return $this->container->get(Environment::class);
    }

    /**
     * @return UrlMatcher
     */
    public function getUrlMatcher()
    {
        return $this->container->get(UrlMatcher::class);
    }

    /**
     * @return PageRepositoryInterface
     */
    public function getPageRepository()
    {
        return $this->container->get(PageRepositoryInterface::class);
    }

    /**
     * @return PluginManager
     */
    public function getPluginManager()
    {
        return $this->container->get(PluginManager::class);
    }

    /**
     * @return EventManager
     */
    public function getEventManager()
    {
        return $this->container->get(EventManager::class);
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->container->get(Config::class);
    }

    /**
     * @return Alias
     */
    public function getAlias()
    {
        return $this->container->get(Alias::class);
    }

    /**
     * @return UrlGenerator
     */
    public function getUrlGenerator()
    {
        return $this->container->get(UrlGenerator::class);
    }

    /**
     * @return CacheInterface
     */
    public function getPageCache()
    {
        return $this->container->get(Cache::class);
    }

    /**
     * @param CacheInterface $cache
     * @return Application
     */
    public function setPageCache(CacheInterface $cache)
    {
        $this->container->set(Cache::class, $cache);
        return $this;
    }

    /**
     * @return Assets
     */
    public function getAssets()
    {
        return $this->container->get(Assets::class);
    }

    /**
     * @return MenuTrail
     */
    public function getMenuTrail()
    {
        return $this->container->get(MenuTrail::class);
    }

    /**
     * @return MenuTree
     */
    public function getMenuTree()
    {
        return $this->container->get(MenuTree::class);
    }

    /**
     * @return HttpFactory
     */
    public function getHttpFactory()
    {
        return $this->container->get(HttpFactory::class);
    }

    /**
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->container->get(Translator::class);
    }

    /**
     * @return MenuList
     */
    public function getMenuList()
    {
        return $this->container->get(MenuList::class);
    }

    /**
     * @return DataRepositoryInterface
     */
    public function getDataRepository()
    {
        return $this->container->get(DataRepositoryInterface::class);
    }

    /**
     * @return SlugGeneratorInterface
     */
    public function getSlugGenerator()
    {
        return $this->container->get(SlugGeneratorInterface::class);
    }

    /**
     * @return TwigRenderer
     */
    public function getTwigRenderer()
    {
        return $this->container->get(TwigRenderer::class);
    }
}
