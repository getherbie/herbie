<?php

declare(strict_types=1);

namespace herbie;

use Ausi\SlugGenerator\SlugGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

defined('HERBIE_DEBUG') or define('HERBIE_DEBUG', false);
define('HERBIE_REQUEST_ATTRIBUTE_PAGE', 'HERBIE_PAGE');
define('HERBIE_REQUEST_ATTRIBUTE_ROUTE', 'HERBIE_ROUTE');
define('HERBIE_REQUEST_ATTRIBUTE_ROUTE_PARAMS', 'HERBIE_ROUTE_PARAMS');
define('HERBIE_VERSION', '2.0.0');
define('HERBIE_API_VERSION', 2);
define('HERBIE_PATH', dirname(__DIR__));
define('HERBIE_PATH_MESSAGES', HERBIE_PATH . '/messages');
define('HERBIE_PATH_SYSPLUGINS', HERBIE_PATH . '/sysplugins');

final class Application
{
    private array $appMiddlewares;
    private string $appPath;
    private Container $container;
    private array $events;
    private array $filters;
    private array $routeMiddlewares;
    private string $sitePath;
    private array $twigFilters;
    private array $twigFunctions;
    private array $twigTests;
    private string $vendorDir;

    /**
     * Application constructor
     * @throws SystemException
     */
    public function __construct(
        string $appPath,
        string $sitePath,
        ?LoggerInterface $logger = null,
        ?CacheInterface $cache = null
    ) {
        #register_shutdown_function(new FatalErrorHandler());
        set_exception_handler(new UncaughtExceptionHandler());

        $this->appMiddlewares = [];
        $this->appPath = normalize_path($appPath);
        $this->events = [];
        $this->filters = [];
        $this->routeMiddlewares = [];
        $this->sitePath = normalize_path($sitePath);
        $this->twigFilters = [];
        $this->twigFunctions = [];
        $this->twigTests = [];
        $this->vendorDir = normalize_path($this->appPath . '/vendor');

        $this->init($logger, $cache);
    }

    /**
     * Initialize the application.
     * @throws SystemException
     */
    private function init(?LoggerInterface $logger = null, ?CacheInterface $cache = null): void
    {
        $logDir = $this->sitePath . '/runtime/log';

        error_reporting(E_ALL);
        ini_set('display_errors', HERBIE_DEBUG ? '1' : '0'); // @phpstan-ignore-line
        ini_set('log_errors', '1');
        ini_set('error_log', sprintf('%s/%s-error.log', $logDir, date('Y-m')));

        $this->container = (new ContainerBuilder($this, $cache, $logger))->build();

        $this->getLogger()->error(sprintf('Directory "%s" does not exist', $logDir));

        if (!is_dir($logDir)) {
            $this->getLogger()->error(sprintf('Directory "%s" does not exist', $logDir));
        }

        if (!is_writable($logDir)) {
            $this->getLogger()->error(sprintf('Directory "%s" is not writable', $logDir));
        }

        $config = $this->container->get(Config::class);

        setlocale(LC_ALL, $config->get('locale'));

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

    public function getAppPath(): string
    {
        return $this->appPath;
    }

    public function getSitePath(): string
    {
        return $this->sitePath;
    }

    public function getVendorDir(): string
    {
        return $this->vendorDir;
    }

    public function getAppMiddlewares(): array
    {
        return $this->appMiddlewares;
    }

    public function getRouteMiddlewares(): array
    {
        return $this->routeMiddlewares;
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

    public function addTwigFilter(TwigFilter $twigFilter): Application
    {
        $this->twigFilters[] = $twigFilter;
        return $this;
    }

    public function getTwigFilters(): array
    {
        return $this->twigFilters;
    }

    public function addTwigFunction(TwigFunction $twigFunction): Application
    {
        $this->twigFunctions[] = $twigFunction;
        return $this;
    }

    public function getTwigFunctions(): array
    {
        return $this->twigFunctions;
    }

    public function addTwigTest(TwigTest $twigTest): Application
    {
        $this->twigTests[] = $twigTest;
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
