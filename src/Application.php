<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

use Ausi\SlugGenerator\SlugGenerator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Tebe\HttpFactory\HttpFactory;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

defined('HERBIE_DEBUG') or define('HERBIE_DEBUG', false);
define('HERBIE_REQUEST_ATTRIBUTE_PAGE', 'HERBIE_PAGE');
define('HERBIE_REQUEST_ATTRIBUTE_ROUTE', 'HERBIE_ROUTE');
define('HERBIE_REQUEST_ATTRIBUTE_ROUTE_PARAMS', 'HERBIE_ROUTE_PARAMS');
define('HERBIE_VERSION', '2.0.0');
define('HERBIE_API_VERSION', 2);

class Application implements LoggerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $filters;

    /**
     * @var string
     */
    private $appPath;

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
    private $applicationMiddlewares;

    /**
     * @var array
     */
    private $routeMiddlewares;

    /**
     * Application constructor.
     * @param $sitePath
     * @param string $vendorDir
     * @throws SystemException
     * @throws \BadMethodCallException
     */
    public function __construct($sitePath, $vendorDir = '../vendor')
    {
        #register_shutdown_function(new FatalErrorHandler());
        set_exception_handler(new UncaughtExceptionHandler());

        $this->filters = [];
        $this->appPath = normalize_path(dirname(__DIR__));
        $this->sitePath = normalize_path($sitePath);
        $this->vendorDir = normalize_path($vendorDir);
        $this->applicationMiddlewares = [];
        $this->routeMiddlewares = [];

        $this->init();
    }

    /**
     * Initialize the application.
     * @throws SystemException
     * @throws \BadMethodCallException
     */
    private function init()
    {
        $logDir = $this->sitePath . '/runtime/log';

        ini_set('display_errors', HERBIE_DEBUG ? '1': '0');
        ini_set('log_errors', '1');
        ini_set('error_log', sprintf('%s/%s-error.log', $logDir, date('Y-m')));
        error_reporting(E_ALL);

        if (!is_dir($logDir)) {
            throw SystemException::directoryNotExist($logDir);
        }
        if (!is_writable($logDir)) {
            throw SystemException::directoryNotWritable($logDir);
        }

        $this->container = $this->initContainer();
        $config = $this->container->get(Config::class);

        setlocale(LC_ALL, $config->get('locale'));

        // Set slug generator to page and page item
        PageItem::setSlugGenerator($this->container->get(SlugGenerator::class));
        Page::setSlugGenerator($this->container->get(SlugGenerator::class));
    }

    /**
     * Initializes and returns container
     * @return Container
     */
    private function initContainer(): Container
    {
        $c = new Container();

        $c->set(ContainerInterface::class, function (Container $c) {
            return $c;
        });

        $c->set(Alias::class, function (Container $c) {
            $paths = $c->get(Config::class)->get('paths');
            return new Alias([
                '@app' => $paths['app'],
                '@asset' => $this->sitePath . '/assets',
                '@media' => $paths['media'],
                '@page' => $paths['pages'],
                '@plugin' => $paths['plugins'],
                '@site' => $this->sitePath,
                '@sysplugin' => $paths['sysPlugins'],
                '@vendor' => $this->vendorDir,
                '@web' => $paths['web'],
                '@snippet' => $paths['app'] . '/templates/snippets'
            ]);
        });

        $c->set(Assets::class, function (Container $c) {
            return new Assets(
                $c->get(Alias::class),
                $c->get(Environment::class)
            );
        });

        $c->set(CacheInterface::class, function () {
            return new NullCache();
        });

        $c->set(Config::class, function (Container $c) {

            $const = [
                'APP_PATH' => rtrim($this->appPath, '/'),
                'SITE_PATH' => rtrim($this->sitePath, '/'),
                'WEB_PATH' => rtrim(preg_replace('#\/?index.php#', '', dirname($_SERVER['SCRIPT_FILENAME'])), '/'),
                'WEB_URL' => rtrim($c->get(Environment::class)->getBaseUrl(), '/')
            ];

            $processor = function (array $data) use ($const) {
                return recursive_array_replace(array_keys($const), array_values($const), $data);
            };

            // default config
            $defaultConfig = load_php_config($this->appPath . '/config/defaults.php', $processor);
            $userConfig = [];
            $pluginConfig = [];

            // user config
            if (is_file($this->sitePath . '/config/main.php')) {
                $userConfig = load_php_config($this->sitePath . '/config/main.php', $processor);
            }

            // plugin configs
            $dir1 = $userConfig['paths']['plugins'] ?? $defaultConfig['paths']['plugins'];
            $dir2 = $userConfig['paths']['sysPlugins'] ?? $defaultConfig['paths']['sysPlugins'];
            $globPattern = "{$dir1}/*/config.php,{$dir2}/*/config.php";
            $configFiles = glob("{" . $globPattern . "}", GLOB_BRACE);
            foreach ($configFiles as $configFile) {
                $config = load_plugin_config($configFile, $processor);
                $pluginName = $config['pluginName'];
                $pluginConfig['plugins'][$pluginName] = $config;
            }

            $config = new Config(array_replace_recursive($defaultConfig, $pluginConfig, $userConfig));
            return $config;
        });

        $c->set(DataRepositoryInterface::class, function (Container $c) {
            $adapter = $c->get(Config::class)->get('components.dataRepository.adapter');
            $path = $c->get(Config::class)->get('paths.data');
            if ($adapter === 'json') {
                return new JsonDataRepository($path);
            }
            return new YamlDataRepository($path);
        });

        $c->set(DownloadMiddleware::class, function (Container $c) {
            return new DownloadMiddleware(
                $c->get(Alias::class),
                $c->get(Config::class)->getAsConfig('components.downloadMiddleware')
            );
        });

        $c->set(Environment::class, function () {
            return new Environment();
        });

        $c->set(ErrorHandlerMiddleware::class, function (Container $c) {
            return new ErrorHandlerMiddleware(
                $c->get(TwigRenderer::class)
            );
        });

        $c->set(EventManager::class, function () {
            return new EventManager(new Event());
        });

        $c->set(FilterChainManager::class, function (Container $c) {
            $manager = new FilterChainManager();
            $manager->attach('renderSegment', $c->get(RenderSegmentFilter::class));
            $manager->attach('renderLayout', $c->get(RenderLayoutFilter::class));
            foreach ($this->filters as $filterName => $filtersPerName) {
                foreach ($filtersPerName as $filter) {
                    $manager->attach($filterName, $filter);
                }
            }
            return $manager;
        });

        $c->set(HttpFactory::class, function () {
            return new HttpFactory();
        });

        $c->set(LoggerInterface::class, function () {
            return new NullLogger();
        });

        $c->set(MiddlewareDispatcher::class, function (Container $c) {
            $pageMiddlewares = array_merge(
                [
                    $c->get(ErrorHandlerMiddleware::class)
                ],
                $this->applicationMiddlewares,
                [
                    $c->get(DownloadMiddleware::class),
                    $c->get(PageResolverMiddleware::class)
                ],
                $c->get(PluginManager::class)->getMiddlewares(),
                [
                    $c->get(PageRendererMiddleware::class)
                ]
            );
            return new MiddlewareDispatcher(
                $pageMiddlewares,
                $this->routeMiddlewares,
                $c->get(Environment::class)->getRoute()
            );
        });

        $c->set(PageFactory::class, function () {
            return new PageFactory();
        });

        $c->set(PagePersistenceInterface::class, function (Container $c) {
            return new FlatfilePagePersistence(
                $c->get(Alias::class),
                $c->get(Config::class)
            );
        });

        $c->set(PageRendererMiddleware::class, function (Container $c) {
            return new PageRendererMiddleware(
                $c->get(CacheInterface::class),
                $c->get(Config::class),
                $c->get(Environment::class),
                $c->get(EventManager::class),
                $c->get(FilterChainManager::class),
                $c->get(HttpFactory::class),
                $c->get(UrlGenerator::class)
            );
        });

        $c->set(PageRepositoryInterface::class, function (Container $c) {
            return new FlatfilePageRepository(
                $c->get(PageFactory::class),
                $c->get(PagePersistenceInterface::class)
            );
        });

        $c->set(PageResolverMiddleware::class, function (Container $c) {
            return new PageResolverMiddleware(
                $c->get(Environment::class),
                $c->get(PageRepositoryInterface::class),
                $c->get(UrlMatcher::class)
            );
        });

        $c->set(PluginManager::class, function (Container $c) {
            return new PluginManager(
                $c->get(Config::class),
                $c->get(EventManager::class),
                $c->get(FilterChainManager::class),
                $c->get(Translator::class),
                $c->get(TwigRenderer::class),
                $c // needed for DI in plugins
            );
        });

        $c->set(RenderLayoutFilter::class, function (Container $c) {
            return new RenderLayoutFilter(
                $c->get(Config::class),
                $c->get(TwigRenderer::class)
            );
        });

        $c->set(RenderSegmentFilter::class, function (Container $c) {
            return new RenderSegmentFilter(
                $c->get(TwigRenderer::class)
            );
        });

        $c->set(ServerRequestInterface::class, function (Container $c) {
            return $c->get(HttpFactory::class)->createServerRequestFromGlobals();
        });

        $c->set(Site::class, function (Container $c) {
            return new Site(
                $c->get(Config::class),
                $c->get(DataRepositoryInterface::class),
                $c->get(Environment::class),
                $c->get(PageRepositoryInterface::class)
            );
        });

        $c->set(SlugGenerator::class, function (Container $c) {
            $options = [
                'locale' => $c->get(Config::class)->get('language'),
                'delimiter' => '-'
            ];
            return new SlugGenerator($options);
        });

        $c->set(Translator::class, function (Container $c) {
            $translator = new Translator($c->get(Config::class)->get('language'));
            $translator->addPath('app', $c->get(Config::class)->get('paths.messages'));
            return $translator;
        });

        $c->set(TwigCoreExtension::class, function (Container $c) {
            return new TwigCoreExtension(
                $c->get(Alias::class),
                $c->get(Assets::class),
                $c->get(Environment::class),
                $c->get(SlugGenerator::class),
                $c->get(Translator::class),
                $c->get(UrlGenerator::class)
            );
        });

        $c->set(TwigPlusExtension::class, function (Container $c) {
            return new TwigPlusExtension(
                $c->get(Environment::class),
                $c->get(PageRepositoryInterface::class),
                $c->get(UrlGenerator::class)
            );
        });

        $c->set(TwigRenderer::class, function (Container $c) {
            return new TwigRenderer(
                $c->get(Config::class),
                $c->get(Environment::class),
                $c->get(EventManager::class),
                $c->get(Site::class),
                $c->get(TwigCoreExtension::class),
                $c->get(TwigPlusExtension::class)
            );
        });

        $c->set(UrlGenerator::class, function (Container $c) {
            return new UrlGenerator(
                $c->get(Config::class),
                $c->get(Environment::class),
                $c->get(ServerRequestInterface::class)
            );
        });

        $c->set(UrlMatcher::class, function (Container $c) {
            return new UrlMatcher(
                $c->get(Config::class)->getAsConfig('components.urlMatcher'),
                $c->get(PageRepositoryInterface::class)
            );
        });

        return $c;
    }

    /**
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    public function run()
    {
        // init components
        $this->getPluginManager()->init();
        $this->getTwigRenderer()->init();
        $this->getTranslator()->init();

        // dispatch middlewares
        $dispatcher = $this->getMiddlewareDispatcher();
        $request = $this->getServerRequest();
        $response = $dispatcher->dispatch($request);

        $this->getEventManager()->trigger('onResponseGenerated', $response);

        $this->emitResponse($response);

        $this->getEventManager()->trigger('onResponseEmitted');

        exit(0);
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
     * @param $middlewareOrPath
     * @param null $middleware
     * @return Application
     */
    public function addMiddleware($middlewareOrPath, $middleware = null) : Application
    {
        if ($middleware) {
            $this->routeMiddlewares[$middlewareOrPath] = $middleware;
        } else {
            $this->applicationMiddlewares[] = $middlewareOrPath;
        }
        return $this;
    }

    /**
     * @param LoggerInterface $interface
     * @return Application
     */
    public function setLogger(LoggerInterface $interface): Application
    {
        $this->container->set(LoggerInterface::class, $interface);
        return $this;
    }

    /**
     * @param CacheInterface $cache
     * @return Application
     */
    public function setCache(CacheInterface $cache)
    {
        $this->container->set(NullCache::class, $cache);
        return $this;
    }

    /**
     * @param TwigFilter $twigFilter
     * @return Application
     */
    public function addTwigFilter(TwigFilter $twigFilter): Application
    {
        $this->getEventManager()->attach('onTwigInitialized', function (Event $event) use ($twigFilter) {
            /** @var TwigRenderer $twig */
            $twig = $event->getTarget();
            $twig->addFilter($twigFilter);
        });
        return $this;
    }

    /**
     * @param TwigFunction $twigFunction
     * @return Application
     */
    public function addTwigFunction(TwigFunction $twigFunction): Application
    {
        $this->getEventManager()->attach('onTwigInitialized', function (Event $event) use ($twigFunction) {
            /** @var TwigRenderer $twig */
            $twig = $event->getTarget();
            $twig->addFunction($twigFunction);
        });
        return $this;
    }

    /**
     * @param string $filterName
     * @param callable $filter
     * @return Application
     */
    public function attachFilter(string $filterName, callable $filter)
    {
        if (!isset($this->filters[$filterName])) {
            $this->filters[$filterName] = [];
        }
        $this->filters[$filterName][] = $filter;
        return $this;
    }

    /**
     * @param string $eventName
     * @param callable $listener
     * @param int $priority
     * @return Application
     */
    public function attachListener(string $eventName, callable $listener, int $priority = 1)
    {
        $this->getEventManager()->attach($eventName, $listener, $priority);
        return $this;
    }

    /**
     * @param TwigTest $twigTest
     * @return Application
     */
    public function addTwigTest(TwigTest $twigTest): Application
    {
        $this->getEventManager()->attach('onTwigInitialized', function (Event $event) use ($twigTest) {
            /** @var TwigRenderer $twig */
            $twig = $event->getTarget();
            $twig->addTest($twigTest);
        });
        return $this;
    }

    /**
     * @return PluginManager
     */
    private function getPluginManager()
    {
        return $this->container->get(PluginManager::class);
    }

    /**
     * @return Translator
     */
    private function getTranslator()
    {
        return $this->container->get(Translator::class);
    }

    /**
     * @return TwigRenderer
     */
    private function getTwigRenderer()
    {
        return $this->container->get(TwigRenderer::class);
    }

    /**
     * @return MiddlewareDispatcher
     */
    private function getMiddlewareDispatcher()
    {
        return $this->container->get(MiddlewareDispatcher::class);
    }

    /**
     * @return ServerRequestInterface
     */
    private function getServerRequest()
    {
        return $this->container->get(ServerRequestInterface::class);
    }

    /**
     * @return EventManager
     */
    private function getEventManager()
    {
        return $this->container->get(EventManager::class);
    }
}
