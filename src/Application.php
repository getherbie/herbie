<?php

declare(strict_types=1);

namespace herbie;

use Ausi\SlugGenerator\SlugGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Console\Command\Command;

final class Application
{
    public const VERSION_SEMVER = '2.0.0';
    public const VERSION_API = 2;
    private array $appMiddlewares;
    private ApplicationPaths $appPaths;
    private Container $container;
    /** @var string[] */
    private array $commands;
    private array $events;
    private array $filters;
    private array $routeMiddlewares;
    private array $twigFilters;
    private array $twigGlobals;
    private array $twigFunctions;
    private array $twigTests;

    /**
     * Application constructor
     * @throws SystemException
     */
    public function __construct(
        ApplicationPaths $paths,
        ?CacheInterface $cache = null,
        ?LoggerInterface $logger = null
    ) {
        #register_shutdown_function(new FatalErrorHandler());
        set_exception_handler(new UncaughtExceptionHandler());

        $this->appMiddlewares = [];
        $this->appPaths = $paths;
        $this->commands = [];
        $this->events = [];
        $this->filters = [];
        $this->routeMiddlewares = [];
        $this->twigFilters = [];
        $this->twigGlobals = [];
        $this->twigFunctions = [];
        $this->twigTests = [];

        $this->init($cache, $logger);
    }

    /**
     * Initialize the application.
     * @throws SystemException
     */
    private function init(?CacheInterface $cache = null, ?LoggerInterface $logger = null): void
    {
        $logDir = $this->appPaths->getSite('/runtime/log');

        error_reporting(self::isDebug() ? E_ALL : E_ERROR);
        ini_set('display_errors', self::isDebug() ? '1' : '0');
        ini_set('display_startup_errors', self::isDebug() ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', sprintf('%s/%s-error.log', $logDir, date_format('Y-m')));

        $this->container = (new ContainerBuilder($this, $cache, $logger))->build();

        if (!is_dir($logDir)) {
            $this->getLogger()->error(sprintf('Directory "%s" does not exist', $logDir));
        }

        if (!is_writable($logDir)) {
            $this->getLogger()->error(sprintf('Directory "%s" is not writable', $logDir));
        }

        $config = $this->getConfig();

        setlocale(LC_ALL, $config->getAsString('locale'));

        // Set slug generator to page and page item
        PageItem::setSlugGenerator($this->container->get(SlugGenerator::class));
        Page::setSlugGenerator($this->container->get(SlugGenerator::class));
    }

    /**
     * @throws SystemException
     * @throws \Twig\Error\LoaderError
     */
    public function run(): void
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

    public function runCli(): void
    {
        $this->getPluginManager()->init();

        $application = new \Symfony\Component\Console\Application();
        $application->setName("-------------------\nHERBIE CMS CLI-Tool\n-------------------");

        /** @var class-string<PluginInterface> $command */
        foreach ($this->getPluginManager()->getCommands() as $command) {
            $params = get_constructor_params_to_inject($command, $this->container);
            /** @var Command $commandInstance */
            $commandInstance = new $command(...$params);
            $application->add($commandInstance);
        }

        $application->run();
    }

    private function emitResponse(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();
        http_response_code($statusCode);
        foreach ($response->getHeaders() as $k => $values) {
            foreach ($values as $v) {
                header(sprintf('%s: %s', $k, $v), false);
            }
        }
        header_remove('X-Powered-By'); // don't expose php
        echo $response->getBody();
    }

    public static function isDebug(): bool
    {
        static $debug;
        if ($debug === null) {
            $debug = (bool)getenv('HERBIE_DEBUG');
        }
        return $debug;
    }

    public static function getHerbiePath(string $append): string
    {
        static $herbiePath;
        if ($herbiePath === null) {
            $herbiePath = dirname(__DIR__);
        }
        return $herbiePath . $append;
    }

    public function getAppPath(): string
    {
        return $this->appPaths->getApp();
    }

    public function getSitePath(): string
    {
        return $this->appPaths->getSite();
    }

    public function getVendorDir(): string
    {
        return $this->appPaths->getVendor();
    }

    public function getWebPath(): string
    {
        return $this->appPaths->getWeb();
    }

    public function getAppMiddlewares(): array
    {
        return $this->appMiddlewares;
    }

    public function getRouteMiddlewares(): array
    {
        return $this->routeMiddlewares;
    }

    public function addCommand(string $command): Application
    {
        $this->commands[] = $command;
        return $this;
    }

    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @param MiddlewareInterface|string $middleware
     */
    public function addAppMiddleware($middleware): Application
    {
        $this->appMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * @param MiddlewareInterface|string $middleware
     */
    public function addRouteMiddleware(string $routeRegex, $middleware): Application
    {
        $this->routeMiddlewares[] = [$routeRegex, $middleware];
        return $this;
    }

    /**
     * @param callable $callable
     */
    public function addTwigFilter(string $name, $callable = null, array $options = []): Application
    {
        $this->twigFilters[] = [$name, $callable, $options];
        return $this;
    }

    public function getTwigFilters(): array
    {
        return $this->twigFilters;
    }

    public function addTwigGlobals(array $twigGlobals): Application
    {
        $this->twigGlobals = $twigGlobals;
        return $this;
    }

    public function getTwigGlobals(): array
    {
        return $this->twigGlobals;
    }

    /**
     * @param callable $callable
     */
    public function addTwigFunction(string $name, $callable = null, array $options = []): Application
    {
        $this->twigFunctions[] = [$name, $callable, $options];
        return $this;
    }

    public function getTwigFunctions(): array
    {
        return $this->twigFunctions;
    }

    /**
     * @param callable $callable
     */
    public function addTwigTest(string $name, $callable = null, array $options = []): Application
    {
        $this->twigTests[] = [$name, $callable, $options];
        return $this;
    }

    public function getTwigTests(): array
    {
        return $this->twigTests;
    }

    public function addFilter(string $filterName, callable $filter): Application
    {
        $this->filters[] = [$filterName, $filter];
        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function addEvent(string $eventName, callable $listener, int $priority = 1): Application
    {
        $this->events[] = [$eventName, $listener, $priority];
        return $this;
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function getConfig(): Config
    {
        return $this->container->get(Config::class);
    }

    public function getCache(): CacheInterface
    {
        return $this->container->get(CacheInterface::class);
    }

    public function getLogger(): LoggerInterface
    {
        return $this->container->get(LoggerInterface::class);
    }

    public function getPluginManager(): PluginManager
    {
        return $this->container->get(PluginManager::class);
    }

    public function getTranslator(): Translator
    {
        return $this->container->get(Translator::class);
    }

    public function getTwigRenderer(): TwigRenderer
    {
        return $this->container->get(TwigRenderer::class);
    }

    public function getMiddlewareDispatcher(): MiddlewareDispatcher
    {
        return $this->container->get(MiddlewareDispatcher::class);
    }

    public function getServerRequest(): ServerRequestInterface
    {
        return $this->container->get(ServerRequestInterface::class);
    }

    public function getEventManager(): EventManager
    {
        return $this->container->get(EventManager::class);
    }
}
