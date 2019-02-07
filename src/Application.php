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
use Ausi\SlugGenerator\SlugOptions;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use Tebe\HttpFactory\HttpFactory;
use Twig_Filter;
use Twig_Function;
use Twig_Test;

defined('HERBIE_DEBUG') or define('HERBIE_DEBUG', false);
define('HERBIE_REQUEST_ATTRIBUTE_PAGE', 'HERBIE_PAGE');
define('HERBIE_REQUEST_ATTRIBUTE_ROUTE_PARAMS', 'HERBIE_ROUTE_PARAMS');
define('HERBIE_VERSION', '2.0.0');

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
     * @param string $sitePath
     * @param string $vendorDir
     * @throws \Exception
     */
    public function __construct($sitePath, $vendorDir = '../vendor')
    {
        $this->sitePath = normalize_path($sitePath);
        $this->vendorDir = normalize_path($vendorDir);
        $this->applicationMiddlewares = [];
        $this->routeMiddlewares = [];
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

        setlocale(LC_ALL, $this->container->get(Configuration::class)->get('locale'));

        // Add custom PSR-4 plugin path to Composer autoloader
        $pluginsPath = $this->container->get(Configuration::class)->paths->plugins;
        $autoload = require($this->vendorDir . '/autoload.php');
        $autoload->addPsr4('herbie\\plugin\\', $pluginsPath);

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

        $c->set(Alias::class, function (Container $c) {
            $config = $c->get(Configuration::class);
            return new Alias([
                '@app' => $config['paths']['app'],
                '@asset' => $this->sitePath . '/assets',
                '@media' => $config['paths']['media'],
                '@page' => $config['paths']['pages'],
                '@plugin' => $config['paths']['plugins'],
                '@site' => $this->sitePath,
                '@vendor' => $this->vendorDir,
                '@web' => $config['paths']['web'],
                '@snippet' => $config['paths']['app'] . '/../templates/snippets'
            ]);
        });

        $c->set(Assets::class, function (Container $c) {
            return new Assets(
                $c->get(Alias::class),
                $c->get(Environment::class)
            );
        });

        $c->set(Cache::class, function () {
            return new Cache();
        });

        $c->set(Configuration::class, function (Container $c) {

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
            $defaults = require(__DIR__ . '/../config/defaults.php');
            $config = new Configuration($defaults);

            // config user
            if (is_file($this->sitePath . '/config/main.php')) {
                $array = require($this->sitePath . '/config/main.php');
            } elseif (is_file($this->sitePath . '/config/main.yml')) {
                $content = file_get_contents($this->sitePath . '/config/main.yml');
                $content = str_replace(array_keys($consts), array_values($consts), $content);
                $array = Yaml::parse($content);
            } else {
                $array = [];
            }
            $userConfig = new Configuration($array);

            // config plugins
            $array = [];
            $dir = $userConfig['paths']['plugins'] ?? $config['paths']['plugins'];
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
            $config->merge(new Configuration($array));

            $config->merge($userConfig);

            return $config;
        });

        $c->set(RenderSegmentFilter::class, function (Container $c) {
            return new RenderSegmentFilter(
                $c->get(TwigRenderer::class)
            );
        });

        $c->set(DataRepositoryInterface::class, function (Container $c) {
            return new YamlDataRepository(
                $c->get(Configuration::class)
            );
        });

        $c->set(DefaultStringFilter::class, function () {
            return new DefaultStringFilter();
        });

        $c->set(DownloadMiddleware::class, function (Container $c) {
            return new DownloadMiddleware(
                $c->get(Alias::class),
                $c->get(Configuration::class)
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

        $c->set(RenderLayoutFilter::class, function (Container $c) {
            return new RenderLayoutFilter(
                $c->get(Configuration::class),
                $c->get(TwigRenderer::class)
            );
        });

        $c->set(LoggerInterface::class, function (Container $c) {
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
                $c->get(Configuration::class)
            );
        });

        $c->set(PageRendererMiddleware::class, function (Container $c) {
            return new PageRendererMiddleware(
                $c->get(Cache::class),
                $c->get(Configuration::class),
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
                $c->get(Configuration::class),
                $c->get(EventManager::class),
                $c->get(FilterChainManager::class),
                $c->get(Translator::class),
                $c->get(TwigRenderer::class),
                $c // needed for DI in plugins
            );
        });

        $c->set(ServerRequestInterface::class, function (Container $c) {
            return $c->get(HttpFactory::class)->createServerRequestFromGlobals();
        });

        $c->set(Site::class, function (Container $c) {
            return new Site(
                $c->get(Configuration::class),
                $c->get(DataRepositoryInterface::class),
                $c->get(Environment::class),
                $c->get(PageRepositoryInterface::class)
            );
        });

        $c->set(SlugGenerator::class, function (Container $c) {
            return new SlugGenerator(
                $c->get(SlugOptions::class)
            );
        });

        $c->set(SlugOptions::class, function (Container $c) {
            $locale = $c->get(Configuration::class)->get('language');
            return new SlugOptions([
                'locale' => $locale,
                'delimiter' => '-'
            ]);
        });

        $c->set(Translator::class, function (Container $c) {
            $translator = new Translator($c->get(Configuration::class)->language);
            $translator->addPath('app', $c->get(Configuration::class)->paths->messages);
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
                $c->get(Configuration::class),
                $c->get(Environment::class),
                $c->get(EventManager::class),
                $c->get(Site::class),
                $c->get(TwigCoreExtension::class),
                $c->get(TwigPlusExtension::class)
            );
        });

        $c->set(UrlGenerator::class, function (Container $c) {
            return new UrlGenerator(
                $c->get(Configuration::class),
                $c->get(Environment::class),
                $c->get(ServerRequestInterface::class)
            );
        });

        $c->set(UrlMatcher::class, function (Container $c) {
            return new UrlMatcher(
                $c->get(Configuration::class)->urlManager,
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
    public function setPageCache(CacheInterface $cache)
    {
        $this->container->set(Cache::class, $cache);
        return $this;
    }

    /**
     * @param Twig_Filter $twigFilter
     * @return Application
     */
    public function addTwigFilter(Twig_Filter $twigFilter): Application
    {
        $this->getEventManager()->attach('onTwigInitialized', function (Event $event) use ($twigFilter) {
            /** @var TwigRenderer $twig */
            $twig = $event->getTarget();
            $twig->addFilter($twigFilter);
        });
        return $this;
    }

    /**
     * @param Twig_Function $twigFunction
     * @return Application
     */
    public function addTwigFunction(Twig_Function $twigFunction): Application
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
     * @param Twig_Test $twigTest
     * @return Application
     */
    public function addTwigTest(Twig_Test $twigTest): Application
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
