<?php

namespace herbie\sysplugins\adminpanel;

use herbie\Alias;
use herbie\Config;
use herbie\DataRepositoryInterface;
use herbie\Finder;
use herbie\PagePersistenceInterface;
use herbie\PageRepositoryInterface;
use herbie\Plugin;
use herbie\Translator;
use herbie\TwigRenderer;
use herbie\UrlManager;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Twig_SimpleFunction;

class AdminpanelPlugin extends Plugin
{
    protected $content;
    protected $panel;
    protected $request;
    private Alias $alias;
    private Config $config;
    private DataRepositoryInterface $dataRepository;
    private Finder $finder;
    private LoggerInterface $logger;
    private PagePersistenceInterface $pagePersistence;
    private PageRepositoryInterface $pageRepository;
    private ResponseFactoryInterface $responseFactory;
    private Translator $translator;
    private TwigRenderer $twigRenderer;
    private UrlManager $urlManager;

    /**
     * DummyPlugin constructor.
     */
    public function __construct(
        Alias $alias,
        Config $config,
        DataRepositoryInterface $dataRepository,
        Finder $finder,
        LoggerInterface $logger,
        PagePersistenceInterface $pagePersistence,
        PageRepositoryInterface $pageRepository,
        ResponseFactoryInterface $responseFactory,
        Translator $translator,
        TwigRenderer $twigRenderer,
        UrlManager $urlManager
    ) {
        $this->alias = $alias;
        $this->config = $config;
        $this->dataRepository = $dataRepository;
        $this->finder = $finder;
        $this->logger = $logger;
        $this->pagePersistence = $pagePersistence;
        $this->pageRepository = $pageRepository;
        $this->responseFactory = $responseFactory;
        $this->translator = $translator;
        $this->twigRenderer = $twigRenderer;
        $this->urlManager = $urlManager;
        session_start();
        #$this->config = DI::get('Config');
        #$this->request = DI::get('Request');
    }

    public function twigFilters(): array
    {
        return [
            ['path_dirname', [$this, 'pathDirname']],
            ['path_basename', [$this, 'pathBasename']],
            ['path_filename', [$this, 'pathFilename']],
            ['path_extension', [$this, 'pathExtension']],
        ];
    }

    public function twigFunctions(): array
    {
        return [
            ['file_contents', [$this, 'fileContents']],
            ['i', [$this, 'icon'], ['is_safe' => ['html']]],
            ['rawdata', [$this, 'rawData']],
        ];
    }

    public function pathDirname(string $path): string
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    public function pathBasename(string $path): string
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    public function pathExtension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    public function pathFilename(string $path): string
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    public function fileContents(string $path): string
    {
        if (!str_starts_with($path, '@')) {
            return '';
        }
        return file_get_contents($this->alias->get($path));
    }

    public function icon(string $icon, int $width = 16, int $height = 16): string
    {
        $path = sprintf('%s/icons/%s.svg', __DIR__, $icon);
        if (!is_file($path)) {
            return '';
        }
        $content = file_get_contents($path);
        $search = ['width="16"', 'height="16"'];
        $replace = ['width="' . $width . '"', 'height="' . $height . '"'];
        return str_replace($search, $replace, $content);
    }

    public function rawData(string $string)
    {
        return addcslashes($string, "\0..\37!@\177");
    }

    public function applicationMiddlewares(): array
    {
        return [[$this, 'appMiddleware']];
    }

    public function appMiddleware(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $pathInfo = trim(str_replace('index.php', '', ltrim($request->getUri()->getPath(), '/')), '/');

        if ($pathInfo !== 'admin') {
            if ($this->isAuthenticated()) {
                $response = $next->handle($request);
                // prepend adminpanel to html body
                $panel = $this->twigRenderer->renderTemplate('@sysplugin/adminpanel/views/panel.twig', [
                    'controller' => 'page'
                ]);
                $regex = '/<body(.*)>/';
                $replace = '<body$1>' . $panel;

                $stream = $response->getBody();
                $stream->rewind();
                $contents = preg_replace($regex, $replace, $stream->getContents());
                $stream->rewind();
                $stream->write($contents);

                return $response->withBody($stream);
            }
            return $next->handle($request);
        }

        $this->logger->debug(__METHOD__);

        $action = 'login';
        if ($this->isAuthenticated()) {
            $queryParams = $request->getQueryParams();
            $action = $queryParams['action'] ?? 'page/index';
        }

        $pos = strpos($action, '/');
        if ($pos === false) {
            $controller = 'default';
        } else {
            $controller = substr($action, 0, $pos);
            $action = substr($action, ++$pos);
        }

        $controllerClass = '\\herbie\\sysplugins\\adminpanel\\controllers\\' . ucfirst($controller) . 'Controller';
        $method = str_replace('-', '', $action) . 'Action';

        $constructorParams = [
            $this->alias,
            $this->config,
            $this->dataRepository,
            $this->finder,
            $this->pagePersistence,
            $this->pageRepository,
            $this->responseFactory,
            $request,
            $this->translator,
            $this->twigRenderer,
            $this->urlManager
        ];

        try {
            $controllerObject = new $controllerClass(...$constructorParams);
            $controllerObject->controller = $controller;
            $controllerObject->action = $action;
            $response = call_user_func_array([$controllerObject, $method], ['request' => $request]);
        } catch (\Exception $e) {
            $controllerObject = new controllers\DefaultController(...$constructorParams);
            $response = $controllerObject->errorAction($request, $e);
        }
        return $response;
    }

    protected function isAuthenticated()
    {
        return !empty($_SESSION['LOGGED_IN']);
    }
}
