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

        $this['request'] = function () use ($app) {
            return Request::createFromGlobals();
        };

        $this['cache.page'] = function () use ($config) {
            if (empty($config['cache']['page']['enable'])) {
                return new Cache\DummyCache();
            }
            return new Cache\PageCache($config['cache']['page']);
        };

        $this['cache.data'] = function () use ($config) {
            if (empty($config['cache']['data']['enable'])) {
                return new Cache\DummyCache();
            }
            return new Cache\DataCache($config['cache']['data']);
        };

        $this['menu'] = function () use ($app, $config) {
            $cache = $app['cache.data'];
            $path = $config['pages']['path'];
            $extensions = $config['pages']['extensions'];
            $builder = new Menu\MenuCollectionBuilder($this['parser'], $cache, $extensions);
            return $builder->build($path);
        };

        $this['tree'] = function () use ($app, $config) {
            $builder = new Menu\MenuTreeBuilder();
            return $builder->build($app['menu']);
        };

        $this['posts'] = function () use ($app, $config) {
            $cache = $app['cache.data'];
            $path = $config['posts']['path'];
            #$extensions = $config['posts']['extensions'];
            #$blogRoute = $config['posts']['blogRoute'];
            $options = [
                'extensions' => $config['posts']['extensions'],
                'blogRoute' => $config['posts']['blogRoute']
            ];
            $builder = new Menu\PostCollectionBuilder($this['parser'], $cache, $options);
            return $builder->build($path);
        };

        $this['paginator'] = function () use ($app) {
            return new Paginator($app['posts'], $this['request']);
        };

        $this['rootPath'] = function () use ($app) {
            $route = $this->getRoute();
            return new Menu\RootPath($app['menu'], $route);
        };

        $this['data'] = function () use ($app, $config) {
            $parser = $app['parser'];
            $loader = new Loader\DataLoader($parser, $config['data']['extensions']);
            return $loader->load($config['data']['path']);
        };

        $this['urlMatcher'] = function () use ($app) {
            return new Url\UrlMatcher($app['menu'], $app['posts']);
        };

        $this['urlGenerator'] = function () use ($app, $config) {
            return new Url\UrlGenerator($app['request'], $config['nice_urls']);
        };

        $this['page'] = function () use ($app) {
            return new Page(); // be sure that we always have a Page object
        };

        $this['shortcode'] = function () use ($config) {
            $tags = isset($config['shortcodes']) ? $config['shortcodes'] : [];
            return new Shortcode($tags);
        };

        $this['twigFilesystem'] = function () use ($app, $config) {

            $loader = new Twig_Loader_Filesystem($config['layouts']['path']);
            $loader->addPath(__DIR__ . '/layouts');
            $loader->addPath(__DIR__ . '/Twig/widgets', 'widget');

            $twig = new Twig_Environment($loader, [
                'debug' => $config['twig']['debug'],
                'cache' => $config['twig']['cache']
            ]);

            if (!empty($config['twig']['debug'])) {
                $twig->addExtension(new Twig_Extension_Debug());
            }
            $twig->addExtension(new Twig\HerbieExtension($app));
            if (!empty($config['imagine'])) {
                $twig->addExtension(new Twig\ImagineExtension($app));
            }
            $this->addTwigPlugins($twig, $config);
            return $twig;
        };

        $this['twigString'] = function () use ($app, $config) {

            $loader1 = new Twig_Loader_Filesystem($config['layouts']['path']);
            $loader1->addPath(__DIR__ . '/layouts');
            $loader2 = new Twig_Loader_String();
            $loaderChain = new Twig_Loader_Chain([$loader1, $loader2]);
            $twig = new Twig_Environment($loaderChain, [
                'debug' => $config['twig']['debug'],
                'cache' => $config['twig']['cache']
            ]);

            if (!empty($config['twig']['debug'])) {
                $twig->addExtension(new Twig_Extension_Debug());
            }

            $twig->addExtension(new Twig\HerbieExtension($app));
            if (!empty($config['imagine'])) {
                $twig->addExtension(new Twig\ImagineExtension($app));
            }
            $this->addTwigPlugins($twig, $config);

            return $twig;
        };

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
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
        $request = Request::createFromGlobals();

        try {

            $response = $this->handle($request);
        } catch (ResourceNotFoundException $e) {

            $content = $this->renderLayout('error.html', ['error' => $e]);
            $response = new Response($content, 404);
        } catch (Exception $e) {

            $content = $this->renderLayout('error.html', ['error' => $e]);
            $response = new Response($content, 500);
        }

        $response->send();
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
            'baseUrl' => $this['request']->getBasePath()
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
            'baseUrl' => $this['request']->getBasePath()
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
}
