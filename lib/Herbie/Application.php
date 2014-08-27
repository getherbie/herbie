<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

use ErrorException;
use Exception;
use Herbie\Exception\ResourceNotFoundException;
use Pimple\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Twig_Environment;
use Twig_Extension_Debug;
use Twig_Loader_Chain;
use Twig_Loader_Filesystem;
use Twig_Loader_String;

/**
 * The application using Pimple as dependency injection container.
 */
class Application extends Container
{

    /**
     * @var string
     */
    public $charset;

    /**
     * @var string
     */
    public $language;

    /**
     * LC_ALL
     * @var string
     */
    public $locale;

    /**
     * @var Response
     */
    public $response;

    /**
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @throws ErrorException
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        // disable error capturing to avoid recursive errors
        restore_error_handler();
        throw new ErrorException($errstr, 500, $errno, $errfile, $errline);
    }

    /**
     * @param string $sitePath
     * @param array $values
     */
    public function __construct($sitePath, array $values = [])
    {
        set_error_handler([$this, 'errorHandler'], error_reporting());

        parent::__construct();

        $app = $this;

        $this['appPath'] = realpath(__DIR__ . '/../../');
        $this['webPath'] = rtrim(dirname($_SERVER['SCRIPT_FILENAME']), '/');
        $this['sitePath'] = realpath($sitePath);

        $this['parser'] = function () {
            return new Parser();
        };

        $config = $this->loadConfiguration();

        setlocale(LC_ALL, $config['locale']);
        $this->charset = $config['charset'];
        $this->language = $config['language'];
        $this->locale = $config['locale'];

        $this['config'] = $config;

        $this['request'] = function ($app) {
            return Request::createFromGlobals();
        };

        $this['cache.page'] = function ($app) {
            if (empty($app['config']['cache']['page']['enable'])) {
                return new Cache\DummyCache();
            }
            return new Cache\PageCache($app['config']['cache']['page']);
        };

        $this['cache.data'] = function ($app) {
            if (empty($app['config']['cache']['data']['enable'])) {
                return new Cache\DummyCache();
            }
            return new Cache\DataCache($app['config']['cache']['data']);
        };

        $this['events'] = function ($app) {
            return new EventDispatcher();
        };

        $this['plugins'] = function ($app) {
            return new Plugins($app);
        };

        $this['menu'] = function ($app) {
            $cache = $app['cache.data'];
            $path = $app['config']['pages']['path'];
            $extensions = $app['config']['pages']['extensions'];
            $builder = new Menu\MenuCollectionBuilder($this['parser'], $cache, $extensions);
            $menu = $builder->build($path);
            $this->fireEvent('onPagesInitialized', new \Symfony\Component\EventDispatcher\Event());
            return $menu;
        };

        $this['tree'] = function ($app) {
            $builder = new Menu\MenuTreeBuilder();
            return $builder->build($app['menu']);
        };

        $this['posts'] = function ($app) {
            $cache = $app['cache.data'];
            $path = $app['config']['posts']['path'];
            #$extensions = $app['config']['posts']['extensions'];
            #$blogRoute = $app['config']['posts']['blogRoute'];
            $options = [
                'extensions' => $app['config']['posts']['extensions'],
                'blogRoute' => $app['config']['posts']['blogRoute']
            ];
            $builder = new Menu\PostCollectionBuilder($this['parser'], $cache, $options);
            return $builder->build($path);
        };

        $this['paginator'] = function ($app) {
            return new Paginator($app['posts'], $app['request']);
        };

        $this['rootPath'] = function ($app) {
            $route = $this->getRoute();
            return new Menu\RootPath($app['menu'], $route);
        };

        $this['data'] = function ($app) {
            $parser = $app['parser'];
            $loader = new Loader\DataLoader($parser, $app['config']['data']['extensions']);
            return $loader->load($app['config']['data']['path']);
        };

        $this['urlMatcher'] = function ($app) {
            return new Url\UrlMatcher($app['menu'], $app['posts']);
        };

        $this['urlGenerator'] = function ($app) {
            return new Url\UrlGenerator($app['request'], $app['config']['nice_urls']);
        };

        $this['page'] = function ($app) {
            return new Page(); // be sure that we always have a Page object
        };

        $this['shortcode'] = function ($app) {
            $tags = isset($app['config']['shortcodes']) ? $app['config']['shortcodes'] : [];
            return new Shortcode($tags);
        };

        $this['twigFilesystem'] = function ($app) {

            $loader = $this->getTwigFilesystemLoader($app['config']);

            $twig = new Twig_Environment($loader, [
                'debug' => $app['config']['twig']['debug'],
                'cache' => $app['config']['twig']['cache']
            ]);

            if (!empty($app['config']['twig']['debug'])) {
                $twig->addExtension(new Twig_Extension_Debug());
            }
            $twig->addExtension(new Twig\HerbieExtension($app));
            if (!empty($app['config']['imagine'])) {
                $twig->addExtension(new Twig\ImagineExtension($app));
            }
            $this->addTwigPlugins($twig, $app['config']);

            return $twig;
        };

        $this['twigString'] = function ($app) {

            $loader1 = $this->getTwigFilesystemLoader($app['config']);
            $loader2 = new Twig_Loader_String();
            $loaderChain = new Twig_Loader_Chain([$loader1, $loader2]);
            $twig = new Twig_Environment($loaderChain, [
                'debug' => $app['config']['twig']['debug'],
                'cache' => $app['config']['twig']['cache']
            ]);

            if (!empty($app['config']['twig']['debug'])) {
                $twig->addExtension(new Twig_Extension_Debug());
            }

            $twig->addExtension(new Twig\HerbieExtension($app));
            if (!empty($app['config']['imagine'])) {
                $twig->addExtension(new Twig\ImagineExtension($app));
            }
            $this->addTwigPlugins($twig, $app['config']);

            return $twig;
        };

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
    }

    /**
     * @param array $config
     * @return Twig_Loader_Filesystem
     */
    private function getTwigFilesystemLoader($config)
    {
        $paths = [];
        if(empty($config['theme'])) {
            $paths[] = $config['layouts']['path'];
        } elseif($config['theme'] == 'default') {
            $paths[] = $config['layouts']['path'] . '/default';
        } else {
            $paths[] = $config['layouts']['path'] . '/' . $config['theme'];
            $paths[] = $config['layouts']['path'] . '/default';
        }
        $paths[] = __DIR__ . '/layouts'; // Fallback

        $loader = new Twig_Loader_Filesystem($paths);
        $loader->addPath(__DIR__ . '/Twig/widgets', 'widget');
        return $loader;
    }

    /**
     * @param Twig_Environment $twig
     * @param array $config
     */
    public function addTwigPlugins(Twig_Environment $twig, array $config)
    {
        if(empty($config['twig']['extend'])) {
            return;
        }

        extract($config['twig']['extend']); // functions, filters, tests

        // Functions
        if (isset($functions)) {
            foreach($this->readPhpFiles($functions) as $file) {
                $included = $this->includePhpFile($file);
                $twig->addFunction($included);
            }
        }

        // Filters
        if (isset($filters)) {
            foreach($this->readPhpFiles($filters) as $file) {
                $included = $this->includePhpFile($file);
                $twig->addFilter($included);
            }
        }

        // Tests
        if (isset($tests)) {
            foreach($this->readPhpFiles($tests) as $file) {
                $included = $this->includePhpFile($file);
                $twig->addTest($included);
            }
        }
    }

    /**
     * @param string $file
     * @return string
     */
    private function includePhpFile($file)
    {
        $app = $this; // Global $app var used by plugins
        return include($file);
    }

    /**
     * @param string $dir
     * @return array
     */
    private function readPhpFiles($dir)
    {
        $dir = rtrim($dir, '/');
        if(empty($dir) || !is_dir($dir)) {
            return [];
        }
        $pattern = $dir . '/*.php';
        return glob($pattern);
    }

    /**
     * @return void
     */
    public function run()
    {
        $this['plugins']->init();

        $this->fireEvent('onPluginsInitialized');

        $request = Request::createFromGlobals();

        try {

            $this->response = $this->handle($request);
        } catch (ResourceNotFoundException $e) {

            $content = $this->renderLayout('error.html', ['error' => $e]);
            $this->response = new Response($content, 404);
        } catch (Exception $e) {

            $content = $this->renderLayout('error.html', ['error' => $e]);
            $this->response = new Response($content, 500);
        }

        $this->fireEvent('onOutputGenerated');

        $this->response->send();

        $this->fireEvent('onOutputRendered');
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request)
    {
        $route = $this->getRoute($request);
        $path = $this['urlMatcher']->match($route);
        $cache = $this['cache.page'];

        $pageLoader = new Loader\PageLoader($this['parser']);
        $this['page'] = $page = $pageLoader->load($path);

        $content = $cache->get($path);
        if ($content === false) {
            $layout = $page->getLayout();
            if (empty($layout)) {
                $content = $this->renderContentSegment(0);
            } else {
                $content = $this->renderLayout($layout);
            }
            $cache->set($path, $content);
        }

        $response = new Response($content);
        $response->headers->set('Content-Type', $page->getContentType());
        return $response;
    }

    /**
     * @param string $layout
     * @param array $arguments
     * @return string
     */
    public function renderLayout($layout, array $arguments = [])
    {
        $arguments = array_merge($arguments, [
            'route' => $this->getRoute(),
            'baseUrl' => $this['request']->getBasePath(),
            'theme' => $this['config']['theme']
        ]);
        return $this['twigFilesystem']->render($layout, $arguments);
    }

    /**
     * @param string $string
     * @param array $arguments
     * @return string
     */
    public function renderString($string, array $arguments = [])
    {
        $arguments = array_merge($arguments, [
            'route' => $this->getRoute(),
            'baseUrl' => $this['request']->getBasePath(),
            'theme' => $this['config']['theme']
        ]);
        return $this['twigString']->render($string, $arguments);
    }

    /**
     * @param string|int $segmentId
     * @return string
     */
    public function renderContentSegment($segmentId)
    {
        $page = $this['page'];
        $segment = $page->getSegment($segmentId);

        $segment = $this['shortcode']->parse($segment);

        $twigged = $this->renderString($segment);

        $formatter = Formatter\FormatterFactory::create($page->getFormat());
        return $formatter->transform($twigged);
    }

    /**
     * @param Request $request
     * @return string
     */
    public function getRoute(Request $request = null)
    {
        if (is_null($request)) {
            $request = $this['request'];
        }
        return trim($request->getPathInfo(), '/');
    }

    /**
     * @param array $default
     * @param array $override
     * @return array
     */
    protected function mergeConfigArrays($default, $override)
    {
        foreach ($override as $key => $value) {
            if (is_array($value)) {
                $array = isset($default[$key]) ? $default[$key] : [];
                $default[$key] = $this->mergeConfigArrays($array, $override[$key]);
            } else {
                $default[$key] = $value;
            }
        }
        return $default;
    }

    /**
     *
     * @return array
     */
    protected function loadConfiguration()
    {
        $config = require(__DIR__ . '/config.php');
        if (is_file($this['sitePath'] . '/config.php')) {
            $userConfig = require($this['sitePath'] . '/config.php');
            return $this->mergeConfigArrays($config, $userConfig);
        }
        if (is_file($this['sitePath'] . '/config.yml')) {
            $content = file_get_contents($this['sitePath'] . '/config.yml');
            $content = str_replace(
                ['APP_PATH', 'WEB_PATH', 'SITE_PATH'], [$this['appPath'], $this['sitePath'], $this['sitePath']], $content
            );
            $userConfig = $this['parser']->parse($content);
            return $this->mergeConfigArrays($config, $userConfig);
        }
        return $config;
    }

    /**
     * @param  string $eventName
     * @param  Event  $event
     * @return Event
     */
    public function fireEvent($eventName, \Symfony\Component\EventDispatcher\Event $event = null)
    {
        return $this['events']->dispatch($eventName, $event);
    }

}
