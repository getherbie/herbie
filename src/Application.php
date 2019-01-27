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
use Herbie\Exception\SystemException;
use Herbie\Middleware\DownloadMiddleware;
use Herbie\Middleware\ErrorHandlerMiddleware;
use Herbie\Middleware\MiddlewareDispatcher;
use Herbie\Middleware\PageRendererMiddleware;
use Herbie\Middleware\PageResolverMiddleware;
use Herbie\Page\Page;
use Herbie\Page\PageFactory;
use Herbie\Page\PageItem;
use Herbie\Persistence\FlatfilePagePersistence;
use Herbie\Persistence\PagePersistenceInterface;
use Herbie\Repository\DataRepositoryInterface;
use Herbie\Repository\FlatfilePageRepository;
use Herbie\Repository\PageRepositoryInterface;
use Herbie\Repository\YamlDataRepository;
use Herbie\Twig\TwigExtension;
use Herbie\Twig\TwigRenderer;
use Herbie\Url\UrlGenerator;
use Herbie\Url\UrlMatcher;
use Psr\Container\ContainerInterface;
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

        setlocale(LC_ALL, $this->container->get(Config::class)->get('locale'));

        // Add custom PSR-4 plugin path to Composer autoloader
        $pluginsPath = $this->container->get(Config::class)->paths->plugins;
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
            $config = $c->get(Config::class);
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

        $c->set(Config::class, function (Container $c) {

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
            } else {
                $array = [];
            }
            $userConfig = new Config($array);

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
            $config->merge(new Config($array));

            $config->merge($userConfig);

            return $config;
        });

        $c->set(DataRepositoryInterface::class, function (Container $c) {
            return new YamlDataRepository(
                $c->get(Config::class)
            );
        });

        $c->set(DownloadMiddleware::class, function (Container $c) {
            return new DownloadMiddleware(
                $c->get(Alias::class),
                $c->get(Config::class)
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

        $c->set(Event::class, function (Container $c) {
            return new Event(
                $c->get(\Zend\EventManager\Event::class)
            );
        });

        $c->set(EventManager::class, function (Container $c) {
            return new EventManager(
                $c->get(\Zend\EventManager\EventManager::class)
            );
        });

        $c->set(HttpFactory::class, function () {
            return new HttpFactory();
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
                $c->get(Cache::class),
                $c->get(Config::class),
                $c->get(Environment::class),
                $c->get(EventManager::class),
                $c->get(HttpFactory::class),
                $c->get(TwigRenderer::class),
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
                $c
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
            return new SlugGenerator(
                $c->get(SlugOptions::class)
            );
        });

        $c->set(SlugOptions::class, function (Container $c) {
            $locale = $c->get(Config::class)->get('language');
            return new SlugOptions([
                'locale' => $locale,
                'delimiter' => '-'
            ]);
        });

        $c->set(Translator::class, function (Container $c) {
            $translator = new Translator($c->get(Config::class)->language);
            $translator->addPath('app', $c->get(Config::class)->paths->messages);
            foreach ($c->get(PluginManager::class)->getPluginPaths() as $key => $dir) {
                $translator->addPath($key, $dir . '/messages');
            }
            return $translator;
        });

        $c->set(TwigExtension::class, function (Container $c) {
            return new TwigExtension(
                $c->get(Alias::class),
                $c->get(Assets::class),
                $c->get(Config::class),
                $c->get(DataRepositoryInterface::class),
                $c->get(Environment::class),
                $c->get(PageRepositoryInterface::class),
                $c->get(SlugGenerator::class),
                $c->get(Translator::class),
                $c->get(UrlGenerator::class)
            );
        });

        $c->set(TwigRenderer::class, function (Container $c) {
            return new TwigRenderer(
                $c->get(Config::class),
                $c->get(Environment::class),
                $c->get(EventManager::class),
                $c->get(Site::class),
                $c->get(TwigExtension::class)
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
                $c->get(Config::class)->urlManager,
                $c->get(PageRepositoryInterface::class)
            );
        });

        $c->set(\Zend\EventManager\Event::class, function () {
            return new \Zend\EventManager\Event();
        });

        $c->set(\Zend\EventManager\EventManager::class, function (Container $c) {
            $zendEventManager = new \Zend\EventManager\EventManager();
            $zendEventManager->setEventPrototype(
                $c->get(Event::class)
            );
            return $zendEventManager;
        });

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

        $dispatcher = $this->getMiddlewareDispatcher();
        $request = $this->getServerRequest();
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
     * @param array $applicationMiddlewares
     * @return Application
     */
    public function setApplicationMiddlewares(array $applicationMiddlewares)
    {
        $this->applicationMiddlewares = $applicationMiddlewares;
        return $this;
    }

    /**
     * @param array $routeMiddlewares
     * @return $this
     */
    public function setRouteMiddlewares(array $routeMiddlewares)
    {
        $this->routeMiddlewares = $routeMiddlewares;
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
     * @param \Twig_Filter $twigFilter
     * @return Application
     */
    public function addTwigFilter(\Twig_Filter $twigFilter): Application
    {
        $this->getEventManager()->attach('onTwigInitialized', function (Event $event) use ($twigFilter) {
            /** @var TwigRenderer $twig */
            $twig = $event->getTarget();
            $twig->addFilter($twigFilter);
        });
        return $this;
    }

    /**
     * @param \Twig_Function $twigFunction
     * @return Application
     */
    public function addTwigFunction(\Twig_Function $twigFunction): Application
    {
        $this->getEventManager()->attach('onTwigInitialized', function (Event $event) use ($twigFunction) {
            /** @var TwigRenderer $twig */
            $twig = $event->getTarget();
            $twig->addFunction($twigFunction);
        });
        return $this;
    }

    /**
     * @param \Twig_Test $twigTest
     * @return Application
     */
    public function addTwigTest(\Twig_Test $twigTest): Application
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
     * @return EventManager
     */
    private function getTranslator()
    {
        return $this->container->get(Translator::class);
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
