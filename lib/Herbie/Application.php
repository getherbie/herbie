<?php

/*
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
use Pimple;
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
class Application extends Pimple
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
    public function exception_error_handler($errno, $errstr, $errfile, $errline)
    {
        // disable error capturing to avoid recursive errors
        restore_error_handler();
        throw new ErrorException($errstr, 500, $errno, $errfile, $errline);
    }

    /**
     * @param string $sitePath
     * @param array $values
     */
    public function __construct($sitePath, array $values = array())
    {
        set_error_handler(array($this, 'exception_error_handler'), error_reporting());

        parent::__construct();

        $app = $this;

        $this['appPath'] = realpath(__DIR__ . '/../../');
        $this['webPath'] = rtrim(dirname($_SERVER['SCRIPT_FILENAME']), '/');
        $this['sitePath'] = realpath($sitePath);

        $this['parser'] = $this->share(function () {
            return new Parser();
        });

        $config = $this->loadConfiguration();

        setlocale(LC_ALL, $config['locale']);
        $this->charset = $config['charset'];
        $this->language = $config['language'];
        $this->locale = $config['locale'];

        $this['config'] = $config;

        $this['request'] = $this->share(function () use ($app) {
            return Request::createFromGlobals();
        });

        $this['cache.page'] = $this->share(function () use ($config) {
            if (empty($config['cache']['page']['enable'])) {
                return new Cache\DummyCache();
            }
            return new Cache\PageCache($config['cache']['page']);
        });

        $this['cache.data'] = $this->share(function () use ($config) {
            if (empty($config['cache']['data']['enable'])) {
                return new Cache\DummyCache();
            }
            return new Cache\DataCache($config['cache']['data']);
        });

        $this['menu'] = $this->share(function () use ($app, $config) {
            $cache = $app['cache.data'];
            $path = $config['pages']['path'];
            $extensions = $config['pages']['extensions'];
            $builder = new Menu\MenuCollectionBuilder($this['parser'], $cache, $extensions);
            return $builder->build($path);
        });

        $this['tree'] = $this->share(function () use ($app, $config) {
            $builder = new Menu\MenuTreeBuilder();
            return $builder->build($app['menu']);
        });

        $this['posts'] = $this->share(function () use ($app, $config) {
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
        });

        $this['paginator'] = $this->share(function () use ($app) {
            return new Paginator($app['posts'], $this['request']);
        });

        $this['rootPath'] = $this->share(function () use ($app) {
            $route = $this->getRoute();
            return new Menu\RootPath($app['menu'], $route);
        });

        $this['data'] = $this->share(function() use ($app, $config) {
            $parser = $app['parser'];
            $loader = new Loader\DataLoader($parser, $config['data']['extensions']);
            return $loader->load($config['data']['path']);
        });

        $this['urlMatcher'] = $this->share(function () use ($app) {
            return new Url\UrlMatcher($app['menu'], $app['posts']);
        });

        $this['urlGenerator'] = $this->share(function () use ($app, $config) {
            return new Url\UrlGenerator($app['request'], $config['nice_urls']);
        });

        $this['page'] = $this->share(function () use ($app) {
            return new Page(); // be sure that we always have a Page object
        });

        $this['twigFilesystem'] = $this->share(function () use ($app, $config) {

            $loader = new Twig_Loader_Filesystem($config['layouts']['path']);
            $twig = new Twig_Environment($loader, [
                'debug' => $config['twig']['debug'],
                'cache' => $config['twig']['cache']
            ]);

            if (!empty($config['twig']['debug'])) {
                $twig->addExtension(new Twig_Extension_Debug());
            }
            $twig->addExtension(new Twig\HerbieExtension($app));
            $this->addTwigPlugins($twig, $config);
            $loader->addPath(__DIR__ . '/Twig/widgets', 'widget');

            return $twig;
        });

        $this['twigString'] = $this->share(function () use ($app, $config) {

            $loader1 = new Twig_Loader_Filesystem($config['layouts']['path']);
            $loader2 = new Twig_Loader_String();
            $loaderChain = new Twig_Loader_Chain(array($loader1, $loader2));
            $twig = new Twig_Environment($loaderChain, [
                'debug' => $config['twig']['debug'],
                'cache' => $config['twig']['cache']
            ]);

            if (!empty($config['twig']['debug'])) {
                $twig->addExtension(new Twig_Extension_Debug());
            }

            $twig->addExtension(new Twig\HerbieExtension($app));
            $this->addTwigPlugins($twig, $config);

            return $twig;
        });

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
        $app = $this;

        // Functions
        if (!empty($config['twig']['extend']['functions'])) {
            $dir = $config['twig']['extend']['functions'];
            if (is_dir($dir)) {
                foreach (scandir($dir) as $file) {
                    if (substr($file, 0, 1) == '.')
                        continue;
                    $function = include($dir . '/' . $file);
                    $twig->addFunction($function);
                }
            }
        }

        // Filters
        if (!empty($config['twig']['extend']['filters'])) {
            $dir = $config['twig']['extend']['filters'];
            if (is_dir($dir)) {
                foreach (scandir($dir) as $file) {
                    if (substr($file, 0, 1) == '.')
                        continue;
                    $filter = include($dir . '/' . $file);
                    $twig->addFilter($filter);
                }
            }
        }

        // Tests
        if (!empty($config['twig']['extend']['tests'])) {
            $dir = $config['twig']['extend']['tests'];
            if (is_dir($dir)) {
                foreach (scandir($dir) as $file) {
                    if (substr($file, 0, 1) == '.')
                        continue;
                    $test = include($dir . '/' . $file);
                    $twig->addTest($test);
                }
            }
        }
    }

    /**
     * @return void
     */
    public function run()
    {
        $request = Request::createFromGlobals();

        try {

            $response = $this->handle($request);
        } catch (Exception\ResourceNotFoundException $e) {

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
    public function renderLayout($layout, array $arguments = array())
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
    public function renderString($string, array $arguments = array())
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

        if (isset($this['config']['pseudo_html'])) {
            $pseudoHtml = $this['config']['pseudo_html'];
            $segment = str_replace(
                explode('|', $pseudoHtml['from']), explode('|', $pseudoHtml['to']), $segment
            );
        }

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
        $route = trim($request->getPathInfo(), '/');
        if (empty($route)) {
            $route = 'index';
        }
        return $route;
    }

    /**
     * @param array $default
     * @param array $override
     * @return array
     * @throws Exception
     */
    protected function mergeConfigArrays($default, $override)
    {
        foreach ($override as $key => $value) {
            if (array_key_exists($key, $default)) {
                if (is_array($value)) {
                    $default[$key] = $this->mergeConfigArrays($default[$key], $override[$key]);
                } else {
                    $default[$key] = $value;
                }
            } else {
                throw new Exception("Config setting $key is not allowed.");
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
        if (is_file($this['sitePath'] . '/config.yml')) {
            $content = file_get_contents($this['sitePath'] . '/config.yml');
            $content = str_replace(
                ['APP_PATH', 'WEB_PATH', 'SITE_PATH'], [$this['appPath'], $this['sitePath'], $this['sitePath']], $content
            );
            $userConfig = $this['parser']->parse($content);
            $config = $this->mergeConfigArrays($config, $userConfig);
        }
        return $config;
    }

}
