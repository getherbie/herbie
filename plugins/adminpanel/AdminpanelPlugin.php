<?php

namespace herbie\sysplugins\adminpanel;

use herbie\Alias;
use herbie\Config;
use Herbie\DI;
use Herbie\Hook;
use Herbie\Loader\FrontMatterLoader;
use Herbie\Menu;
use herbie\PagePersistenceInterface;
use herbie\PageRepositoryInterface;
use herbie\Plugin;
use herbie\Translator;
use herbie\TwigRenderer;
use herbie\UrlManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Tebe\HttpFactory\HttpFactory;
use Twig_SimpleFunction;

class AdminpanelPlugin extends Plugin
{
    protected $content;
    protected $panel;
    protected $request;
    private Alias $alias;
    private Config $config;
    private HttpFactory $httpFactory;
    private LoggerInterface $logger;
    private PagePersistenceInterface $pagePersistence;
    private PageRepositoryInterface $pageRepository;
    private Translator $translator;
    private TwigRenderer $twigRenderer;
    private UrlManager $urlManager;

    /**
     * DummyPlugin constructor.
     */
    public function __construct(
        Alias $alias,
        Config $config,
        HttpFactory $httpFactory,
        LoggerInterface $logger,
        PagePersistenceInterface $pagePersistence,
        PageRepositoryInterface $pageRepository,
        Translator $translator,
        TwigRenderer $twigRenderer,
        UrlManager $urlManager
    ) {
        $this->alias = $alias;
        $this->config = $config;
        $this->httpFactory = $httpFactory;
        $this->logger = $logger;
        $this->pagePersistence = $pagePersistence;
        $this->pageRepository = $pageRepository;
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
        return str_replace(['{width}', '{height}'], [$width, $height], $content);
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
        $method = $action . 'Action';

        $constructorParams = [
            $this->alias,
            $this->config,
            $this->pagePersistence,
            $this->pageRepository,
            $request,
            $this->translator,
            $this->twigRenderer,
            $this->urlManager
        ];

        $controllerObject = new $controllerClass(...$constructorParams);
        if (!method_exists($controllerObject, $method)) {
            $controllerObject = new controllers\DefaultController(...$constructorParams);
            $method = 'errorAction';
        }
        $controllerObject->controller = $controller;
        $controllerObject->action = $action;

        $params = ['request' => $request];
        $responseOrContent = call_user_func_array([$controllerObject, $method], $params);
        $statusCode = http_response_code();

        if ($responseOrContent instanceof ResponseInterface) {
            return $responseOrContent->withStatus($statusCode);
        }

        $response = $this->httpFactory->createResponse();
        $response->getBody()->write($responseOrContent);
        return $response->withHeader('Content-Type', 'text/html')->withStatus($statusCode);
    }

    public function install()
    {
        #Hook::attach('pluginsInitialized', [$this, 'pluginsInitialized']);
        #Hook::attach('twigInitialized', [$this, 'addTwigFunction']);
        #Hook::attach('outputGenerated', [$this, 'outputGenerated']);
    }

    public function addTwigFunction($twig)
    {
        $function = new Twig_SimpleFunction('rawdata', function ($string) {
            return addcslashes($string, "\0..\37!@\177");
        });
        $twig->addFunction($function);
    }

    public function pluginsInitialized()
    {
        if ($this->config->isEmpty('plugins.config.adminpanel.no_page')) {
            $this->config->push('pages.extra_paths', '@sysplugin/adminpanel/pages');
        }
    }

    public function outputGenerated($response)
    {
        // return if response is not successful
        if (!$response->isSuccessful()) {
            return;
        }

        if (!$this->isAdmin()) {
            if ($this->isAuthenticated()) {
                // prepend adminpanel to html body
                $controller = (0 === strpos(DI::get('Page')->path, '@post')) ? 'post' : 'page';
                $panel = DI::get('Twig')->render('@sysplugin/adminpanel/views/panel.twig', [
                    'controller' => $controller
                ]);
                $regex = '/<body(.*)>/';
                $replace = '<body$1>' . $panel;
                $content = preg_replace($regex, $replace, $response->getContent());
                $response->setContent($content);
            }
        } else {
            $action = $this->isAuthenticated() ? $this->request->getQuery('action', 'page/index') : 'login';
            $pos = strpos($action, '/');
            if ($pos === false) {
                $controller = 'default';
            } else {
                $controller = substr($action, 0, $pos);
                $action = substr($action, ++$pos);
            }

            $controllerClass = '\\herbie\\plugin\\adminpanel\\controllers\\' . ucfirst($controller) . 'Controller';
            $method = $action . 'Action';

            $controllerObject = new $controllerClass();
            if (!method_exists($controllerObject, $method)) {
                $controllerObject = new controllers\DefaultController();
                $method = 'errorAction';
            }
            $controllerObject->controller = $controller;
            $controllerObject->action = $action;

            $params = ['request' => $this->request];
            $content = call_user_func_array([$controllerObject, $method], $params);
            $response->setContent($content);
        }
    }

    protected function isAdmin()
    {
        return !empty(DI::get('Page')->adminpanel);
    }

    protected function isAuthenticated()
    {
        return !empty($_SESSION['LOGGED_IN']);
    }

    /**
     * @return string
     */
    protected function getPathAlias()
    {
        return $this->config->get(
            'plugins.config.adminpanel.page.adminpanel',
            '@sysplugin/adminpanel/pages/adminpanel.html'
        );
    }
}
