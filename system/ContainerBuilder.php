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
use UnexpectedValueException;

final readonly class ContainerBuilder
{
    private const string PACKAGE_GUZZLE_HTTP_PSR7 = 'guzzlehttp/psr7';
    private const string PACKAGE_LAMINAS_DIACTOROS = 'laminas/laminas-diactoros';
    private const string PACKAGE_NYHOLM_PSR7 = 'nyholm/psr7';
    private const string PACKAGE_NYHOLM_PSR7_SERVER = 'nyholm/psr7-server';
    private const string PACKAGE_SLIM_PSR7 = 'slim/psr7';

    public function __construct(private Application $app, private ?CacheInterface $cache = null, private ?LoggerInterface $logger = null) {}

    public function build(): ContainerInterface
    {
        $c = new Container();

        $c->set(Application::class, $this->app);

        $c->set(ContainerInterface::class, fn(ContainerInterface $c) => $c);

        $c->set(Alias::class, function (ContainerInterface $c) {
            $config = $c->get(Config::class);
            $paths = $config->get('paths');
            $theme = trim($config->getAsString('theme'));
            $themePath = $paths['themes'] . '/' . $theme;
            return new Alias([
                '@app' => $paths['app'],
                '@asset' => $this->app->getSitePath() . '/assets',
                '@media' => $paths['media'],
                '@page' => $paths['pages'],
                '@site' => $this->app->getSitePath(),
                '@theme' => $themePath,
                // the default of paths.plugins is Herbie's own plugin directory
                '@plugin' => $paths['plugins'],
                '@vendor' => $this->app->getVendorPath(),
                '@web' => $paths['web'],
                '@snippet' => $paths['app'] . '/templates/snippets',
            ]);
        });

        $c->set(Assets::class, function (ContainerInterface $c) {
            $config = $c->get(Config::class);
            $theme = trim($config->getAsString('theme'));
            $themeParent = trim($config->getAsString('themeParent'));
            $themeFallbackPaths = [];
            if ($themeParent !== '' && $themeParent !== $theme) {
                $parentPath = trim($config->getAsString('paths.themes')) . '/' . $themeParent;
                if (is_dir($parentPath)) {
                    $themeFallbackPaths[] = $parentPath;
                }
            }
            return new Assets(
                $c->get(Alias::class),
                $this->app->getBaseUrl(),
                $themeFallbackPaths,
            );
        });

        if ($this->cache) {
            $c->set(CacheInterface::class, $this->cache);
        } else {
            $c->set(CacheInterface::class, function (ContainerInterface $c) {
                $config = $c->get(Config::class)->getAsArray('components.fileCache');
                if (isset($config['path'])) {
                    return new FileCache(
                        $c->get(Alias::class)->get($config['path']),
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
                'WEB_URL' => str_untrailing_slash($this->app->getBaseUrl()),
            ];

            $processor = (fn(array $data) => recursive_array_replace(array_keys($const), array_values($const), $data));

            // default config
            $defaultConfigPath = Application::getHerbiePath('/config/defaults.php');
            $defaultConfig = load_php_config($defaultConfigPath, $processor);

            // user config
            $userConfigPath = $this->app->getSitePath() . '/config/main.php';
            $userConfig = [];
            if (is_file($userConfigPath)) {
                $userConfig = load_php_config($userConfigPath, $processor);
            }

            // system, composer and local plugin configs, the discovery is cached outside of debug mode
            $pluginCacheFile = Application::isDebug()
                ? null
                : $this->app->getSitePath() . '/runtime/cache/system/plugins.php';
            $systemPluginPath = $userConfig['paths']['plugins'] ?? $defaultConfig['paths']['plugins'];
            $localPluginPath = $userConfig['paths']['localPlugins'] ?? $defaultConfig['paths']['localPlugins'];
            $pluginConfigs = (new PluginDiscovery($pluginCacheFile))
                ->discover($systemPluginPath, $localPluginPath, $processor);

            // the order is important here
            $userConfig['plugins'] = array_replace_recursive(
                $pluginConfigs,
                $userConfig['plugins'] ?? [],
            );

            $allConfig = array_replace_recursive($defaultConfig, $userConfig);

            $config = new Config($allConfig);
            if (trim($config->getAsString('theme')) === '') {
                throw new UnexpectedValueException('Config "theme" must not be empty.', 500);
            }
            return $config;
        });

        $c->set(DataRepositoryInterface::class, function (ContainerInterface $c) {
            $adapter = $c->get(Config::class)->get('components.dataRepository.adapter');
            $path = $c->get(Config::class)->get('paths.data');
            if ($adapter === 'json') {
                return new JsonDataRepository($path);
            }
            return new YamlDataRepository($path);
        });

        $c->set(DownloadMiddleware::class, fn(ContainerInterface $c) => new DownloadMiddleware(
            $c->get(Alias::class),
            $c->get(StreamFactoryInterface::class),
            $c->get(ResponseFactoryInterface::class),
            $c->get(Config::class)->getAsArray('components.downloadMiddleware'),
        ));

        $c->set(ErrorHandlerMiddleware::class, fn(ContainerInterface $c) => new ErrorHandlerMiddleware(
            $c->get(PageRepositoryInterface::class),
            $c->get(ResponseFactoryInterface::class),
            $c->get(TwigRenderer::class),
            $c->get(UrlManager::class),
        ));

        $c->set(EventManager::class, fn() => new EventManager());

        $c->set(Finder::class, function (ContainerInterface $c) {
            $config = $c->get(Config::class);
            return new Finder([
                'extensions' => str_explode_filtered($config->getAsString('fileExtensions.pages'), ','),
                'path' => $config->getAsString('paths.pages'),
            ]);
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
                        $config['level'],
                    );
                }
                return new NullLogger();
            });
        }

        $c->set(MiddlewareDispatcher::class, fn(ContainerInterface $c) => new MiddlewareDispatcher(
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
            $c->get(UrlManager::class)->parseRequest()[0],
        ));

        $c->set(PageFactory::class, fn(ContainerInterface $c) => new PageFactory($c->get(SlugGenerator::class)));

        $c->set(PagePersistenceInterface::class, function (ContainerInterface $c) {
            $options = $c->get(Config::class)->getAsArray('components.flatFilePagePersistence');
            return new FlatFilePagePersistence(
                $c->get(Alias::class),
                $c->get(CacheInterface::class),
                $c->get(Finder::class),
                $options,
            );
        });

        $c->set(PageRendererMiddleware::class, function (ContainerInterface $c) {
            $options = $c->get(Config::class)->getAsArray('components.pageRendererMiddleware');
            return new PageRendererMiddleware(
                $c->get(CacheInterface::class),
                $c->get(EventManager::class),
                $c->get(ResponseFactoryInterface::class),
                $c->get(UrlManager::class),
                $options,
            );
        });

        $c->set(PageRepositoryInterface::class, fn(ContainerInterface $c) => new FlatFilePageRepository(
            $c->get(PageFactory::class),
            $c->get(PagePersistenceInterface::class),
        ));

        $c->set(PageResolverMiddleware::class, fn(ContainerInterface $c) => new PageResolverMiddleware(
            $c->get(PageRepositoryInterface::class),
            $c->get(UrlManager::class),
        ));

        $c->set(PluginManager::class, fn(ContainerInterface $c) => new PluginManager(
            $c->get(Config::class),
            $c->get(EventManager::class),
            $c->get(Translator::class),
            $c->get(LoggerInterface::class),
            $c, // needed for DI in plugins
        ));

        $c->set(ResponseFactoryInterface::class, function () {
            $package = $this->preferredPsr17Package();
            if ($package === null) {
                $this->throwPsr17Exception();
            }
            return match ($package) {
                self::PACKAGE_LAMINAS_DIACTOROS => new \Laminas\Diactoros\ResponseFactory(),
                self::PACKAGE_GUZZLE_HTTP_PSR7 => new \GuzzleHttp\Psr7\HttpFactory(),
                self::PACKAGE_NYHOLM_PSR7 => new \Nyholm\Psr7\Factory\Psr17Factory(),
                self::PACKAGE_SLIM_PSR7 => new \Slim\Psr7\Factory\ResponseFactory(),
                default => throw new \RuntimeException('Unsupported PSR-17 package'),
            };
        });

        $c->set(ServerRequestFactoryInterface::class, function () {
            $package = $this->preferredPsr17Package();
            if ($package === null) {
                $this->throwPsr17Exception();
            }
            return match ($package) {
                self::PACKAGE_LAMINAS_DIACTOROS => new \Laminas\Diactoros\ServerRequestFactory(),
                self::PACKAGE_GUZZLE_HTTP_PSR7 => new \GuzzleHttp\Psr7\HttpFactory(),
                self::PACKAGE_NYHOLM_PSR7 => new \Nyholm\Psr7\Factory\Psr17Factory(),
                self::PACKAGE_SLIM_PSR7 => new \Slim\Psr7\Factory\ServerRequestFactory(),
                default => throw new \RuntimeException('Unsupported PSR-17 package'),
            };
        });

        $c->set(ServerRequestInterface::class, function (ContainerInterface $c) {
            $package = $this->preferredPsr17Package();
            if ($package === null) {
                $this->throwPsr17Exception();
            }
            if ($package === self::PACKAGE_LAMINAS_DIACTOROS) {
                return \Laminas\Diactoros\ServerRequestFactory::fromGlobals();
            }
            if ($package === self::PACKAGE_GUZZLE_HTTP_PSR7) {
                return \GuzzleHttp\Psr7\ServerRequest::fromGlobals();
            }
            if ($package === self::PACKAGE_NYHOLM_PSR7) {
                if (!composer_package_installed(self::PACKAGE_NYHOLM_PSR7_SERVER)) {
                    $message = 'To enable full PSR-17 support, install %s.';
                    throw new Exception(sprintf($message, self::PACKAGE_NYHOLM_PSR7_SERVER));
                }
                /** @var \Nyholm\Psr7\Factory\Psr17Factory $serverRequestFactory */
                $serverRequestFactory = $c->get(ServerRequestFactoryInterface::class);
                return (new \Nyholm\Psr7Server\ServerRequestCreator(
                    $serverRequestFactory,
                    $serverRequestFactory,
                    $serverRequestFactory,
                    $serverRequestFactory,
                ))->fromGlobals();
            }
            /** @var \Slim\Psr7\Factory\ServerRequestFactory $serverRequestFactory */
            $serverRequestFactory = $c->get(ServerRequestFactoryInterface::class);
            return $serverRequestFactory->createFromGlobals();
        });

        $c->set(StreamFactoryInterface::class, function () {
            $package = $this->preferredPsr17Package();
            if ($package === null) {
                $this->throwPsr17Exception();
            }
            return match ($package) {
                self::PACKAGE_LAMINAS_DIACTOROS => new \Laminas\Diactoros\StreamFactory(),
                self::PACKAGE_GUZZLE_HTTP_PSR7 => new \GuzzleHttp\Psr7\HttpFactory(),
                self::PACKAGE_NYHOLM_PSR7 => new \Nyholm\Psr7\Factory\Psr17Factory(),
                self::PACKAGE_SLIM_PSR7 => new \Slim\Psr7\Factory\StreamFactory(),
                default => throw new \RuntimeException('Unsupported PSR-17 package'),
            };
        });

        $c->set(Site::class, fn(ContainerInterface $c) => new Site(
            $c->get(Config::class),
            $c->get(DataRepositoryInterface::class),
            $c->get(PageRepositoryInterface::class),
            $c->get(UrlManager::class),
        ));

        $c->set(SlugGenerator::class, function (ContainerInterface $c) {
            $options = [
                'locale' => $c->get(Config::class)->get('language'),
                'delimiter' => '-',
            ];
            return new SlugGenerator($options);
        });

        $c->set(Translator::class, function (ContainerInterface $c) {
            $translator = new Translator($c->get(Config::class)->get('language'));
            $translator->addPath('app', Application::getHerbiePath('/messages'));
            return $translator;
        });

        $c->set(TwigRenderer::class, fn(ContainerInterface $c) => new TwigRenderer(
            $c->get(Config::class),
            $c->get(EventManager::class),
            $c->get(LoggerInterface::class),
            $c->get(Site::class),
        ));

        $c->set(UrlManager::class, function (ContainerInterface $c) {
            $config = $c->get(Config::class)->getAsArray('components.urlManager');
            $config['baseUrl'] = $this->app->getBaseUrl();
            $config['scriptUrl'] = $this->app->getScriptUrl();
            return new UrlManager(
                $c->get(ServerRequestInterface::class),
                $config,
            );
        });

        return $c;
    }

    private function preferredPsr17Package(): ?string
    {
        if (composer_package_installed(self::PACKAGE_LAMINAS_DIACTOROS)) {
            return self::PACKAGE_LAMINAS_DIACTOROS;
        }
        if (composer_package_installed(self::PACKAGE_GUZZLE_HTTP_PSR7)) {
            return self::PACKAGE_GUZZLE_HTTP_PSR7;
        }
        if (composer_package_installed(self::PACKAGE_NYHOLM_PSR7)) {
            return self::PACKAGE_NYHOLM_PSR7;
        }
        if (composer_package_installed(self::PACKAGE_SLIM_PSR7)) {
            return self::PACKAGE_SLIM_PSR7;
        }
        return null;
    }

    private function throwPsr17Exception(): never
    {
        $packages = [
            [self::PACKAGE_LAMINAS_DIACTOROS],
            [self::PACKAGE_GUZZLE_HTTP_PSR7],
            [self::PACKAGE_NYHOLM_PSR7, self::PACKAGE_NYHOLM_PSR7_SERVER],
            [self::PACKAGE_SLIM_PSR7],
        ];

        $packages = array_map(fn(array $items): string => join(' with ', $items), $packages);

        $message = 'To enable PSR-17 support, install one of the following Composer packages: %s.';
        throw new Exception(sprintf($message, join(', ', $packages)), 500);
    }
}
