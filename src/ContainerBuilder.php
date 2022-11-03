<?php

declare(strict_types=1);

namespace herbie;

use Ausi\SlugGenerator\SlugGenerator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use Tebe\HttpFactory\HttpFactory;

final class ContainerBuilder
{
    private Application $app;
    private ?CacheInterface $cache;
    private ?LoggerInterface $logger;

    public function __construct(
        Application $app,
        ?CacheInterface $cache = null,
        ?LoggerInterface $logger = null
    ) {
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
            $aliases = $c->get(Config::class)->get('components.alias');
            return new Alias($aliases);
        });

        $c->set(Assets::class, function (ContainerInterface $c) {
            return new Assets(
                $c->get(Alias::class),
                $c->get(Environment::class)
            );
        });

        if ($this->cache) {
            $c->set(CacheInterface::class, $this->cache);
        } else {
            $c->set(CacheInterface::class, function (ContainerInterface $c) {
                $options = $c->get(Config::class)->getAsArray('components.fileCache');
                if (isset($options['path'])) {
                    return new FileCache([
                        'path' => $c->get(Alias::class)->get($options['path'])
                    ]);
                }
                return new NullCache();
            });
        }

        $c->set(Config::class, function (ContainerInterface $c) {
            $const = [
                'APP_PATH' => str_untrailing_slash($this->app->getAppPath()),
                'HERBIE_PATH' => Application::getHerbiePath(''),
                'SITE_PATH' => str_untrailing_slash($this->app->getSitePath()),
                'VENDOR_PATH' => str_untrailing_slash($this->app->getVendorDir()),
                'WEB_PATH' => str_untrailing_slash($this->app->getWebPath()),
                'WEB_URL' => str_untrailing_slash($c->get(Environment::class)->getBaseUrl())
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
            $systemPluginPath = Application::getHerbiePath('/sysplugins');
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
            $adapter = $c->get(Config::class)->getAsString('components.dataRepository.adapter');
            $options = ['path' => $c->get(Config::class)->getAsString('paths.data')];
            if ($adapter === 'json') {
                return new JsonDataRepository($options);
            }
            return new YamlDataRepository($options);
        });

        $c->set(DownloadMiddleware::class, function (ContainerInterface $c) {
            return new DownloadMiddleware(
                $c->get(Alias::class),
                $c->get(Config::class)->getAsArray('components.downloadMiddleware')
            );
        });

        $c->set(Environment::class, function () {
            return new Environment();
        });

        $c->set(ErrorHandlerMiddleware::class, function (ContainerInterface $c) {
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
            $c->set(LoggerInterface::class, function (ContainerInterface $c) {
                $options = $c->get(Config::class)->getAsArray('components.fileLogger');
                if (isset($options['path'], $options['channel'], $options['level'])) {
                    $options['path'] = $c->get(Alias::class)->get($options['path']);
                    return new FileLogger($options);
                }
                return new NullLogger();
            });
        }

        $c->set(MiddlewareDispatcher::class, function (ContainerInterface $c) {
            return new MiddlewareDispatcher(
                [
                    $c->get(ErrorHandlerMiddleware::class) // only one at the moment
                ],
                $c->get(PluginManager::class)->getAppMiddlewares(),
                $c->get(PluginManager::class)->getRouteMiddlewares(),
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

        $c->set(PagePersistenceInterface::class, function (ContainerInterface $c) {
            $config = $c->get(Config::class);
            $options = [
                'pagePath' => $config->getAsString('paths.pages'),
                'pageFileExtensions' => str_explode_filtered($config->getAsString('fileExtensions.pages'), ',')
            ];
            return new FlatfilePagePersistence(
                $c->get(Alias::class),
                $options
            );
        });

        $c->set(PageRendererMiddleware::class, function (ContainerInterface $c) {
            $options = $c->get(Config::class)->getAsArray('components.pageRendererMiddleware');
            return new PageRendererMiddleware(
                $c->get(CacheInterface::class),
                $c->get(Environment::class),
                $c->get(EventManager::class),
                $c->get(FilterChainManager::class),
                $c->get(HttpFactory::class),
                $c->get(UrlGenerator::class),
                $options
            );
        });

        $c->set(PageRepositoryInterface::class, function (ContainerInterface $c) {
            return new FlatfilePageRepository(
                $c->get(PageFactory::class),
                $c->get(PagePersistenceInterface::class)
            );
        });

        $c->set(PageResolverMiddleware::class, function (ContainerInterface $c) {
            return new PageResolverMiddleware(
                $c->get(Environment::class),
                $c->get(PageRepositoryInterface::class),
                $c->get(UrlMatcher::class)
            );
        });

        $c->set(PluginManager::class, function (ContainerInterface $c) {
            $config = $c->get(Config::class);
            $options = [
                'enabledSystemPlugins' => str_explode_filtered($config->getAsString('enabledSysPlugins'), ','),
                'enabledComposerOrLocalPlugins' => str_explode_filtered($config->getAsString('enabledPlugins'), ','),
                'pluginConfigurations' => $config->getAsArray('plugins')
            ];
            return new PluginManager(
                $c->get(EventManager::class),
                $c->get(FilterChainManager::class),
                $c->get(Translator::class),
                $c->get(LoggerInterface::class),
                $c, // NOTE needed for DI in plugins
                $options
            );
        });

        $c->set(ServerRequestInterface::class, function (ContainerInterface $c) {
            return $c->get(HttpFactory::class)->createServerRequestFromGlobals();
        });

        $c->set(Site::class, function (ContainerInterface $c) {
            return new Site(
                $c->get(Config::class), // needed for site.config global
                $c->get(DataRepositoryInterface::class),
                $c->get(Environment::class),
                $c->get(PageRepositoryInterface::class)
            );
        });

        $c->set(SlugGenerator::class, function (ContainerInterface $c) {
            $options = [
                'locale' => $c->get(Config::class)->getAsString('language'),
                'delimiter' => '-'
            ];
            return new SlugGenerator($options);
        });

        $c->set(Translator::class, function (ContainerInterface $c) {
            $translator = new Translator([
                'language' => $c->get(Config::class)->getAsString('language')
            ]);
            $translator->addPath('app', Application::getHerbiePath('/messages'));
            return $translator;
        });

        $c->set(TwigRenderer::class, function (ContainerInterface $c) {
            return new TwigRenderer(
                $c->get(Config::class), // needed for global context
                $c->get(Environment::class),
                $c->get(EventManager::class),
                $c->get(LoggerInterface::class),
                $c->get(Site::class)
            );
        });

        $c->set(UrlGenerator::class, function (ContainerInterface $c) {
            return new UrlGenerator(
                $c->get(Environment::class),
                $c->get(ServerRequestInterface::class),
                ['niceUrls' => $c->get(Config::class)->getAsBool('niceUrls')]
            );
        });

        $c->set(UrlMatcher::class, function (ContainerInterface $c) {
            return new UrlMatcher(
                $c->get(PageRepositoryInterface::class),
                $c->get(Config::class)->getAsArray('components.urlMatcher')
            );
        });

        return $c;
    }
}
