<?php

declare(strict_types=1);

namespace herbie;

use Ausi\SlugGenerator\SlugGenerator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Tebe\HttpFactory\HttpFactory;

final class ContainerBuilder
{
    private Application $app;
    private ?CacheInterface $cache;
    private ?LoggerInterface $logger;

    public function __construct(Application $app, ?CacheInterface $cache = null, ?LoggerInterface $logger = null)
    {
        $this->app = $app;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function build(): Container
    {
        $c = new Container();

        $c->set(Application::class, $this->app);

        $c->set(ContainerInterface::class, function (Container $c) {
            return $c;
        });

        $c->set(Alias::class, function (Container $c) {
            $paths = $c->get(Config::class)->get('paths');
            return new Alias([
                '@app' => $paths['app'],
                '@asset' => $this->app->getSitePath() . '/assets',
                '@media' => $paths['media'],
                '@page' => $paths['pages'],
                '@plugin' => $paths['plugins'],
                '@site' => $this->app->getSitePath(),
                '@sysplugin' => HERBIE_PATH_SYSPLUGINS,
                '@vendor' => $this->app->getVendorDir(),
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

        if ($this->cache) {
            $c->set(CacheInterface::class, $this->cache);
        } else {
            $c->set(CacheInterface::class, function () {
                return new NullCache();
            });
        }

        $c->set(Config::class, function (Container $c) {

            $const = [
                'APP_PATH' => rtrim($this->app->getAppPath(), '/'),
                'SITE_PATH' => rtrim($this->app->getSitePath(), '/'),
                'WEB_PATH' => rtrim(preg_replace('#\/?index.php#', '', dirname($_SERVER['SCRIPT_FILENAME'])), '/'),
                'WEB_URL' => rtrim($c->get(Environment::class)->getBaseUrl(), '/')
            ];

            $processor = function (array $data) use ($const) {
                return recursive_array_replace(array_keys($const), array_values($const), $data);
            };

            // default config
            $defaultConfigPath = HERBIE_PATH . '/config/defaults.php';
            $defaultConfig = load_php_config($defaultConfigPath, $processor);

            // user config
            $userConfigPath = $this->app->getSitePath() . '/config/main.php';
            $userConfig = [];
            if (is_file($userConfigPath)) {
                $userConfig = load_php_config($userConfigPath, $processor);
            }

            // system plugin configs
            $systemPluginPath = HERBIE_PATH_SYSPLUGINS;
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

        $c->set(FilterChainManager::class, function () {
            return new FilterChainManager();
        });

        $c->set(HttpFactory::class, function () {
            return new HttpFactory();
        });

        if ($this->logger) {
            $c->set(LoggerInterface::class, $this->logger);
        } else {
            $c->set(LoggerInterface::class, function () {
                return new NullLogger();
            });
        }

        $c->set(MiddlewareDispatcher::class, function (Container $c) {
            return new MiddlewareDispatcher(
                [
                    $c->get(ErrorHandlerMiddleware::class) // only one at the moment
                ],
                array_merge(
                    $c->get(PluginManager::class)->getAppMiddlewares(),
                    $this->app->getAppMiddlewares()
                ),
                array_merge(
                    $c->get(PluginManager::class)->getRouteMiddlewares(),
                    $this->app->getRouteMiddlewares(),
                ),
                [
                    $c->get(DownloadMiddleware::class),
                    $c->get(PageResolverMiddleware::class),
                    $c->get(PageRendererMiddleware::class)
                ],
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
                $c->get(LoggerInterface::class),
                $c // needed for DI in plugins
            );
        });

        $c->set(RenderLayoutFilter::class, function () {
            return new RenderLayoutFilter();
        });

        $c->set(RenderSegmentFilter::class, function () {
            return new RenderSegmentFilter();
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
            $translator->addPath('app', HERBIE_PATH_MESSAGES);
            return $translator;
        });

        $c->set(TwigRenderer::class, function (Container $c) {
            return new TwigRenderer(
                $c->get(Config::class),
                $c->get(Environment::class),
                $c->get(EventManager::class),
                $c->get(LoggerInterface::class),
                $c->get(Site::class)
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
}