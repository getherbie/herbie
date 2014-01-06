<?php

namespace Herbie;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The application using Pimple as dependency injection container.
 *
 * @author Thomas Breuss <thomas.breuss@zephir.ch>
 */
class Application extends \Pimple
{

    /**
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @throws \ErrorException
     */
    public function exception_error_handler($errno, $errstr, $errfile, $errline ) {
        throw new \ErrorException($errstr, 500, $errno, $errfile, $errline);
    }

    /**
     * @param string $sitePath
     * @param array $values
     */
    public function __construct($sitePath, array $values = array())
    {
        set_error_handler(array($this, 'exception_error_handler'));

        parent::__construct();

        $app = $this;

        $this['appPath'] = realpath(__DIR__ . '/../../');
        $this['webPath'] = rtrim(dirname($_SERVER['SCRIPT_FILENAME']), '/');
        $this['sitePath'] = realpath($sitePath);

        $this['parser'] = $this->share(function () {
            return new \Symfony\Component\Yaml\Parser();
        });

        $config = $this->loadConfiguration();

        $this['config'] = $config;

        $this['request'] = $this->share(function () use ($app) {
            return Request::createFromGlobals();
        });

        $this['cache.page'] = $this->share(function () use ($config) {
            if(empty($config['cache']['page']['enable'])) {
                return new Cache\DummyCache();
            }
            return new Cache\PageCache($config['cache']['page']);
        });

        $this['cache.data'] = $this->share(function () use ($config) {
            if(empty($config['cache']['data']['enable'])) {
                return new Cache\DummyCache();
            }
            return new Cache\DataCache($config['cache']['data']);
        });

        $this['menu'] = $this->share(function () use ($app, $config) {
            $cache = $app['cache.data'];
            $builder = new Menu\MenuCollectionBuilder($config['pages']['path'], $cache);
            return $builder->build();
        });

        $this['tree'] = $this->share(function () use ($app, $config) {
            $builder = new Menu\MenuTreeBuilder($app['menu']);
            return $builder->build();
        });

        $this['posts'] = $this->share(function () use ($app, $config) {
            $cache = $app['cache.data'];
            $builder = new Blog\PostCollectionBuilder($config['posts']['path'], $cache);
            return $builder->build();
        });

        $this['rootPath'] = $this->share(function () use ($app) {
            $route = $this->getRoute();
            return new Menu\RootPath($app['menu'], $route);
        });

        $this['data'] = $this->share(function() use ($app, $config) {
            $parser = $app['parser'];
            $loader = new Loader\DataLoader($config['data']['path'], $parser, $config['data']['extensions']);
            return $loader->load();
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

            $loader = new \Twig_Loader_Filesystem($config['layouts']['path']);
            $twig = new \Twig_Environment($loader, [
                'debug' => $config['twig']['debug'],
                'cache' => $config['twig']['cache']
            ]);

            if(!empty($config['twig']['debug'])) {
                $twig->addExtension(new \Twig_Extension_Debug());
            }
            $twig->addExtension(new Twig\HerbieExtension($app));
            $this->addTwigPlugins($twig, $config);

            return $twig;

        });

        $this['twigString'] = $this->share(function () use ($app, $config) {

            $loader = new \Twig_Loader_String();
            $twig = new \Twig_Environment($loader, [
                'debug' => $config['twig']['debug'],
                'cache' => $config['twig']['cache']
            ]);

            if(!empty($config['twig']['debug'])) {
                $twig->addExtension(new \Twig_Extension_Debug());
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
     * @param \Twig_Environment $twig
     * @param array $config
     */
    public function addTwigPlugins(\Twig_Environment $twig, array $config)
    {
        $app = $this;
        
        // Functions
        if(!empty($config['twig']['extend']['functions'])) {
            $dir = $config['twig']['extend']['functions'];
            if(is_dir($dir)) {
                foreach (scandir($dir) as $file) {
                    if(substr($file, 0, 1) == '.') continue;
                    $function = include($dir . '/' . $file);
                    $twig->addFunction($function);
                }
            }
        }

        // Filters
        if(!empty($config['twig']['extend']['filters'])) {
            $dir = $config['twig']['extend']['filters'];
            if(is_dir($dir)) {
                foreach (scandir($dir) as $file) {
                    if(substr($file, 0, 1) == '.') continue;
                    $filter = include($dir . '/' . $file);
                    $twig->addFilter($filter);
                }
            }
        }

        // Tests
        if(!empty($config['twig']['extend']['tests'])) {
            $dir = $config['twig']['extend']['tests'];
            if(is_dir($dir)) {
                foreach (scandir($dir) as $file) {
                    if(substr($file, 0, 1) == '.') continue;
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

            $content = $this->handle($request);
            $response = new Response($content);

        } catch (\Herbie\Exception\ResourceNotFoundException $e) {

            $content = $this->renderFile('error.html', ['error' => $e]);
            $response = new Response($content, 404);

        } catch (Exception $e) {

            $content = $this->renderFile('error.html', ['error' => $e]);
            $response = new Response($content, 500);
        }

        $response->send();
    }

    /**
     * @param Request $request
     * @return string
     */
    public function handle(Request $request)
    {
        $route = $this->getRoute($request);
        $path = $this['urlMatcher']->match($route);
        $cache = $this['cache.page'];

        $cached = $cache->get($path);
        if($cached === false) {

            $pageLoader = new Loader\PageLoader($path, $this['parser']);

            $page = $this['page'];
            $page->load($pageLoader);

            $template = $page->getLayout();
            $content = $this->renderFile($template);
            $cache->set($path, $content);
            return $content;
        }
        return $cached;
    }

    /**
     * @param string $template
     * @param array $arguments
     * @return string
     */
    public function renderFile($template, array $arguments = array())
    {
        $arguments = array_merge($arguments, [
            'route' => $this->getRoute(),
            'baseUrl' => $this['request']->getBaseUrl()
        ]);
        return $this['twigFilesystem']->render($template, $arguments);
    }

    /**
     * @param string $string
     * @param array $arguments
     * @return string
     */
    public function renderString($string, array $arguments = array())
    {
        return $this['twigString']->render($string, $arguments);
    }

    /**
     * @param Request $request
     * @return string
     */
    public function getRoute(Request $request = null)
    {
        if(is_null($request)) {
            $request = $this['request'];
        }
        $route = trim($request->getPathInfo(), '/');
        if(empty($route)) {
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
      foreach($override as $key => $value)
      {
        if(array_key_exists($key, $default)) {
            if(is_array($value)) {
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
        if(is_file($this['webPath'] . '/site/config.yml')) {
            $content = file_get_contents($this['webPath'] . '/site/config.yml');
            $content = str_replace(
                ['APP_PATH', 'WEB_PATH', 'SITE_PATH'],
                [$this['appPath'], $this['webPath'], $this['sitePath']],
                $content
            );
            $userConfig = $this['parser']->parse($content);
            $config = $this->mergeConfigArrays($config, $userConfig);
        }
        return $config;
    }

}