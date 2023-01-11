<?php

declare(strict_types=1);

namespace herbie;

use Ausi\SlugGenerator\SlugGenerator;
use Exception;
use herbie\middlewares\DownloadMiddleware;
use herbie\middlewares\ErrorHandlerMiddleware;
use herbie\middlewares\PageRendererMiddleware;
use herbie\middlewares\PageResolverMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

final class ContainerBuilder
{
    private const PACKAGE_GUZZLE_HTTP_PSR7 = 'guzzlehttp/psr7';
    private const PACKAGE_LAMINAS_DIACTOROS = 'laminas/laminas-diactoros';
    private const PACKAGE_NYHOLM_PSR7 = 'nyholm/psr7';
    private const PACKAGE_NYHOLM_PSR7_SERVER = 'nyholm/psr7-server';
    private const PACKAGE_SLIM_PSR7 = 'slim/psr7';

    private Application $app;
    private ?CacheInterface $cache;
    private ?LoggerInterface $logger;

    public function __construct(Application $app, ?CacheInterface $cache = null, ?LoggerInterface $logger = null)
    {
        $this->app = $app;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function build(): ContainerInterface
    {
        $c = new Container();

        $c->set(Application::class, $this->app);

        $c->set(ContainerInterface::class, function (ContainerInterface $c) {
            return $c;
        });

        $c->set(Alias::class, function (ContainerInterface $c) {
            $paths = $c->get(Config::class)->get('paths');
            return new Alias([
                '@app' => $paths['app'],
                '@asset' => $this->app->getSitePath() . '/assets',
                '@media' => $paths['media'],
                '@page' => $paths['pages'],
                '@plugin' => $paths['plugins'],
                '@site' => $this->app->getSitePath(),
                '@sysplugin' => Application::getHerbiePath('/plugins'),
                '@vendor' => $this->app->getVendorPath(),
                '@web' => $paths['web'],
                '@snippet' => $paths['app'] . '/templates/snippets'
            ]);
        });

        $c->set(Assets::class, function (ContainerInterface $c) {
            return new Assets(
                $c->get(Alias::class),
                $this->app->getBaseUrl()
            );
        });

        if ($this->cache) {
            $c->set(CacheInterface::class, $this->cache);
        } else {
            $c->set(CacheInterface::class, function (ContainerInterface $c) {
                $config = $c->get(Config::class)->getAsArray('components.fileCache');
                if (isset($config['path'])) {
                    return new FileCache(
                        $c->get(Alias::class)->get($config['path'])
                    );
                }
                return new NullCache();
            });
        }

        $c->set(Config::class, function (ContainerInterface $c) {
            $const = [
                'APP_PATH' => str_untrailing_slash($this->app->getApplicationPath()),
                'SITE_PATH' => str_untrailing_slash($this->app->getSitePath()),
                'WEB_PATH' => str_untrailing_slash($this->app->getWebPath()),
                'WEB_URL' => str_untrailing_slash($this->app->getBaseUrl())
            ];

            $processor = function (array $data) use ($const) {
                return recursive_array_replace(array_keys($const), array_values($const), $data);
            };

            // default config
            $defaultConfigPath = Application::getHerbiePath('/config/defaults.php');
            $defaultConfig = load_php_config($defaultConfigPath, $processor);

            // user config
            $userConfigPath = $this->app->getSitePath() . '/config/main.php';
            $userConfig = [];
            if (is_file($userConfigPath)) {
                $userConfig = load_php_config($userConfigPath, $processor);
            }

            // system plugin configs
            $systemPluginPath = Application::getHerbiePath('/plugins');
            $systemPluginConfigs = load_plugin_configs($systemPluginPath, 'system', $processor);

            // composer plugin configs
            $composerPluginConfigs = load_composer_plugin_configs();

            // local plugin configs
            $localPluginPath = $userConfig['paths']['plugins'] ?? $defaultConfig['paths']['plugins'];
            $localPluginConfigs = load_plugin_configs($localPluginPath, 'local', $processor);

            // the order is important here
            $userConfig['plugins'] = array_replace_recursive(
                $systemPluginConfigs,
                $composerPluginConfigs,
                $localPluginConfigs,
                $userConfig['plugins'] ?? []
            );

            $allConfig = array_replace_recursive($defaultConfig, $userConfig);

            return new Config($allConfig);
        });

        $c->set(DataRepositoryInterface::class, function (ContainerInterface $c) {
            $adapter = $c->get(Config::class)->get('components.dataRepository.adapter');
            $path = $c->get(Config::class)->get('paths.data');
            if ($adapter === 'json') {
                return new JsonDataRepository($path);
            }
            return new YamlDataRepository($path);
        });

        $c->set(DownloadMiddleware::class, function (ContainerInterface $c) {
            return new DownloadMiddleware(
                $c->get(Alias::class),
                $c->get(StreamFactoryInterface::class),
                $c->get(ResponseFactoryInterface::class),
                $c->get(Config::class)->getAsArray('components.downloadMiddleware'),
            );
        });

        $c->set(ErrorHandlerMiddleware::class, function (ContainerInterface $c) {
            return new ErrorHandlerMiddleware(
                $c->get(ResponseFactoryInterface::class),
                $c->get(TwigRenderer::class)
            );
        });

        $c->set(EventManager::class, function () {
            return new EventManager();
        });

        $c->set(FlatFileIterator::class, function (ContainerInterface $c) {
            $config = $c->get(Config::class);
            return new FlatFileIterator(
                $config->getAsString('paths.pages'),
                str_explode_filtered($config->getAsString('fileExtensions.pages'), ',')
            );
        });

        if ($this->logger) {
            $c->set(LoggerInterface::class, $this->logger);
        } else {
            $c->set(LoggerInterface::class, function (ContainerInterface $c) {
                $config = $c->get(Config::class)->getAsArray('components.fileLogger');
                if (isset($config['path'], $config['channel'], $config['level'])) {
                    return new FileLogger(
                        $c->get(Alias::class)->get($config['path']),
                        $config['channel'],
                        $config['level']
                    );
                }
                return new NullLogger();
            });
        }

        $c->set(MiddlewareDispatcher::class, function (ContainerInterface $c) {
            return new MiddlewareDispatcher(
                [
                    $c->get(ErrorHandlerMiddleware::class), // only one at the moment
                    $c->get(PageResolverMiddleware::class),
                ],
                $c->get(PluginManager::class)->getApplicationMiddlewares(),
                $c->get(PluginManager::class)->getRouteMiddlewares(),
                [
                    $c->get(DownloadMiddleware::class),
                    $c->get(PageRendererMiddleware::class),
                ],
                $c->get(UrlManager::class)->parseRequest()[0]
            );
        });

        $c->set(PageFactory::class, function () {
            return new PageFactory();
        });

        $c->set(PagePersistenceInterface::class, function (ContainerInterface $c) {
            $options = $c->get(Config::class)->getAsArray('components.flatFilePagePersistence');
            return new FlatFilePagePersistence(
                $c->get(Alias::class),
                $c->get(CacheInterface::class),
                $c->get(FlatFileIterator::class),
                $options
            );
        });

        $c->set(PageRendererMiddleware::class, function (ContainerInterface $c) {
            $options = $c->get(Config::class)->getAsArray('components.pageRendererMiddleware');
            return new PageRendererMiddleware(
                $c->get(CacheInterface::class),
                $c->get(EventManager::class),
                $c->get(ResponseFactoryInterface::class),
                $c->get(UrlManager::class),
                $options
            );
        });

        $c->set(PageRepositoryInterface::class, function (ContainerInterface $c) {
            return new FlatFilePageRepository(
                $c->get(PageFactory::class),
                $c->get(PagePersistenceInterface::class)
            );
        });

        $c->set(PageResolverMiddleware::class, function (ContainerInterface $c) {
            return new PageResolverMiddleware(
                $c->get(PageRepositoryInterface::class),
                $c->get(UrlManager::class)
            );
        });

        $c->set(PluginManager::class, function (ContainerInterface $c) {
            return new PluginManager(
                $c->get(Config::class),
                $c->get(EventManager::class),
                $c->get(Translator::class),
                $c->get(LoggerInterface::class),
                $c // needed for DI in plugins
            );
        });

        $c->set(ResponseFactoryInterface::class, function () {
            if (composer_package_installed(self::PACKAGE_LAMINAS_DIACTOROS)) {
                return new \Laminas\Diactoros\ResponseFactory();
            }
            if (composer_package_installed(self::PACKAGE_GUZZLE_HTTP_PSR7)) {
                return new \GuzzleHttp\Psr7\HttpFactory(); // factory for all psr interfaces
            }
            if (composer_package_installed(self::PACKAGE_NYHOLM_PSR7)) {
                return new \Nyholm\Psr7\Factory\Psr17Factory(); // factory for all psr interfaces
            }
            if (composer_package_installed(self::PACKAGE_SLIM_PSR7)) {
                return new \Slim\Psr7\Factory\ResponseFactory();
            }
            $this->throwPsr17Exception();
        });

        $c->set(ServerRequestFactoryInterface::class, function () {
            if (composer_package_installed(self::PACKAGE_LAMINAS_DIACTOROS)) {
                return new \Laminas\Diactoros\ServerRequestFactory();
            }
            if (composer_package_installed(self::PACKAGE_GUZZLE_HTTP_PSR7)) {
                return new \GuzzleHttp\Psr7\HttpFactory(); // factory for all psr interfaces
            }
            if (composer_package_installed(self::PACKAGE_NYHOLM_PSR7)) {
                return new \Nyholm\Psr7\Factory\Psr17Factory(); // factory for all psr interfaces
            }
            if (composer_package_installed(self::PACKAGE_SLIM_PSR7)) {
                return new \Slim\Psr7\Factory\ServerRequestFactory();
            }
            $this->throwPsr17Exception();
        });

        $c->set(ServerRequestInterface::class, function (ContainerInterface $c) {
            if (composer_package_installed(self::PACKAGE_LAMINAS_DIACTOROS)) {
                return \Laminas\Diactoros\ServerRequestFactory::fromGlobals();
            }
            if (composer_package_installed(self::PACKAGE_GUZZLE_HTTP_PSR7)) {
                return \GuzzleHttp\Psr7\ServerRequest::fromGlobals();
            }
            if (composer_package_installed(self::PACKAGE_NYHOLM_PSR7)) {
                if (composer_package_installed(self::PACKAGE_NYHOLM_PSR7_SERVER)) {
                    /** @var \Nyholm\Psr7\Factory\Psr17Factory $serverRequestFactory */
                    $serverRequestFactory = $c->get(ServerRequestFactoryInterface::class);
                    return (new \Nyholm\Psr7Server\ServerRequestCreator(
                        $serverRequestFactory,
                        $serverRequestFactory,
                        $serverRequestFactory,
                        $serverRequestFactory
                    ))->fromGlobals();
                }
                $message = 'To enable full PSR-17 support, install %s.';
                throw new Exception(sprintf($message, self::PACKAGE_NYHOLM_PSR7_SERVER));
            }
            if (composer_package_installed(self::PACKAGE_SLIM_PSR7)) {
                /** @var \Slim\Psr7\Factory\ServerRequestFactory $serverRequestFactory */
                $serverRequestFactory = $c->get(ServerRequestFactoryInterface::class);
                return $serverRequestFactory->createFromGlobals();
            }
            $this->throwPsr17Exception();
        });

        $c->set(StreamFactoryInterface::class, function () {
            if (composer_package_installed(self::PACKAGE_LAMINAS_DIACTOROS)) {
                return new \Laminas\Diactoros\StreamFactory();
            }
            if (composer_package_installed(self::PACKAGE_GUZZLE_HTTP_PSR7)) {
                return new \GuzzleHttp\Psr7\HttpFactory(); // factory for all psr interfaces
            }
            if (composer_package_installed(self::PACKAGE_NYHOLM_PSR7)) {
                return new \Nyholm\Psr7\Factory\Psr17Factory(); // factory for all psr interfaces
            }
            if (composer_package_installed(self::PACKAGE_SLIM_PSR7)) {
                return new \Slim\Psr7\Factory\StreamFactory();
            }
            $this->throwPsr17Exception();
        });

        $c->set(Site::class, function (ContainerInterface $c) {
            return new Site(
                $c->get(Config::class),
                $c->get(DataRepositoryInterface::class),
                $c->get(PageRepositoryInterface::class),
                $c->get(UrlManager::class),
            );
        });

        $c->set(SlugGenerator::class, function (ContainerInterface $c) {
            $options = [
                'locale' => $c->get(Config::class)->get('language'),
                'delimiter' => '-'
            ];
            return new SlugGenerator($options);
        });

        $c->set(Translator::class, function (ContainerInterface $c) {
            $translator = new Translator($c->get(Config::class)->get('language'));
            $translator->addPath('app', Application::getHerbiePath('/messages'));
            return $translator;
        });

        $c->set(TwigRenderer::class, function (ContainerInterface $c) {
            return new TwigRenderer(
                $c->get(Config::class),
                $c->get(EventManager::class),
                $c->get(LoggerInterface::class),
                $c->get(Site::class),
            );
        });

        $c->set(UrlManager::class, function (ContainerInterface $c) {
            $config = $c->get(Config::class)->getAsArray('components.urlManager');
            $config['baseUrl'] = $this->app->getBaseUrl();
            $config['scriptUrl'] = $this->app->getScriptUrl();
            return new UrlManager(
                $c->get(ServerRequestInterface::class),
                $config
            );
        });

        return $c;
    }

    private function throwPsr17Exception(): void
    {
        $packages = [
            [self::PACKAGE_LAMINAS_DIACTOROS],
            [self::PACKAGE_GUZZLE_HTTP_PSR7],
            [self::PACKAGE_NYHOLM_PSR7, self::PACKAGE_NYHOLM_PSR7_SERVER],
            [self::PACKAGE_SLIM_PSR7],
        ];

        $packages = array_map(function (array $items): string {
            return join(' with ', $items);
        }, $packages);

        $message = 'To enable PSR-17 support, install one of the following Composer packages: %s.';
        throw new Exception(sprintf($message, join(', ', $packages)), 500);
    }
}
