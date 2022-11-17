<?php

declare(strict_types=1);

namespace herbie;

use Ausi\SlugGenerator\SlugGenerator;
use herbie\event\PluginsInitializedEvent;
use herbie\event\ResponseEmittedEvent;
use herbie\event\ResponseGeneratedEvent;
use herbie\event\TranslatorInitializedEvent;
use herbie\event\TwigInitializedEvent;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Symfony\Component\Console\Command\Command;

final class Application
{
    public const VERSION_SEMVER = '2.0.0';
    public const VERSION_API = 2;
    private array $applicationMiddlewares;
    private ApplicationPaths $applicationPaths;
    private ?string $baseUrl;
    /** @var string[] */
    private array $consoleCommands;
    private ContainerInterface $container;
    private array $eventListeners;
    private array $routeMiddlewares;
    private ?string $scriptFile;
    private ?string $scriptUrl;
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

        $this->applicationMiddlewares = [];
        $this->applicationPaths = $paths;
        $this->baseUrl = null;
        $this->consoleCommands = [];
        $this->eventListeners = [];
        $this->routeMiddlewares = [];
        $this->scriptFile = null;
        $this->scriptUrl = null;
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
        $logDir = $this->applicationPaths->getSite('/runtime/log');

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
        $eventManager = $this->getEventManager();

        // init components
        $this->getPluginManager()->init();
        $this->getTwigRenderer()->init();
        $translator = $this->getTranslator();
        $translator->init();
        $eventManager->dispatch(new TranslatorInitializedEvent($translator));

        // dispatch middlewares
        $dispatcher = $this->getMiddlewareDispatcher();
        $request = $this->getServerRequest();
        $response = $dispatcher->dispatch($request);

        /** @var ResponseGeneratedEvent $responseGeneratedEvent */
        $responseGeneratedEvent = $this->getEventManager()->dispatch(new ResponseGeneratedEvent($response));
        $response = $responseGeneratedEvent->getResponse();

        $this->emitResponse($response);

        $this->getEventManager()->dispatch(new ResponseEmittedEvent($this));
    }

    public function runCli(): void
    {
        $this->getPluginManager()->init();

        $application = new \Symfony\Component\Console\Application();
        $application->setName("-------------------\nHERBIE CMS CLI-Tool\n-------------------");

        /** @var class-string<PluginInterface> $command */
        foreach ($this->getPluginManager()->getConsoleCommands() as $command) {
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

    public function getScriptFile(): string
    {
        if (isset($this->scriptFile)) {
            return $this->scriptFile;
        }

        if (isset($_SERVER['SCRIPT_FILENAME'])) {
            return $_SERVER['SCRIPT_FILENAME'];
        }

        throw new RuntimeException('Unable to determine the entry script file path.');
    }

    public function getScriptUrl(): string
    {
        if ($this->scriptUrl === null) {
            $scriptFile = $this->getScriptFile();
            $scriptName = basename($scriptFile);
            $phpSelf = $_SERVER['PHP_SELF'] ?? null;
            if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
                $this->scriptUrl = $_SERVER['SCRIPT_NAME'];
            } elseif (isset($phpSelf) && basename($phpSelf) === $scriptName) {
                $this->scriptUrl = $phpSelf;
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
                $this->scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
            } elseif (isset($phpSelf) && ($pos = strpos($phpSelf, '/' . $scriptName)) !== false) {
                $this->scriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $scriptName;
            } elseif (!empty($_SERVER['DOCUMENT_ROOT']) && strpos($scriptFile, $_SERVER['DOCUMENT_ROOT']) === 0) {
                $this->scriptUrl = str_replace([$_SERVER['DOCUMENT_ROOT'], '\\'], ['', '/'], $scriptFile);
            } else {
                throw new RuntimeException('Unable to determine the entry script URL.');
            }
        }

        return $this->scriptUrl;
    }

    public function getBaseUrl(): string
    {
        if ($this->baseUrl === null) {
            $this->baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/');
        }

        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public function setScriptFile(string $scriptFile): void
    {
        $this->scriptFile = $scriptFile;
    }

    public function setScriptUrl(string $scriptUrl): void
    {
        $this->scriptUrl = $scriptUrl;
    }

    public function getApplicationPath(): string
    {
        return $this->applicationPaths->getApp();
    }

    public function getSitePath(): string
    {
        return $this->applicationPaths->getSite();
    }

    public function getVendorPath(): string
    {
        return $this->applicationPaths->getVendor();
    }

    public function getWebPath(): string
    {
        return $this->applicationPaths->getWeb();
    }

    public function getApplicationMiddlewares(): array
    {
        return $this->applicationMiddlewares;
    }

    public function getRouteMiddlewares(): array
    {
        return $this->routeMiddlewares;
    }

    public function addConsoleCommand(string $command): Application
    {
        $this->consoleCommands[] = $command;
        return $this;
    }

    public function getConsoleCommands(): array
    {
        return $this->consoleCommands;
    }

    /**
     * @param MiddlewareInterface|string $middleware
     */
    public function addApplicationMiddleware($middleware): Application
    {
        $this->applicationMiddlewares[] = $middleware;
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

    /**
     * @param mixed $mixed
     */
    public function addTwigGlobal(string $name, $mixed): Application
    {
        $this->twigGlobals[] = [$name, $mixed];
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

    public function addEventListener(string $eventName, callable $listener, int $priority = 1): Application
    {
        $this->eventListeners[] = [$eventName, $listener, $priority];
        return $this;
    }

    public function getEventListeners(): array
    {
        return $this->eventListeners;
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
