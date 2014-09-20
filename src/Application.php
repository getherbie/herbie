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

use Herbie\Exception\ResourceNotFoundException;
use Pimple\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
     * @throws \ErrorException
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        // disable error capturing to avoid recursive errors
        restore_error_handler();
        throw new \ErrorException($errstr, 500, $errno, $errfile, $errline);
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

        $config = new Config($app);

        setlocale(LC_ALL, $config->get('locale'));
        $this->charset = $config->get('charset');
        $this->language = $config->get('language');
        $this->locale = $config->get('locale');

        $this['config'] = $config;

        $this['request'] = Request::createFromGlobals();

        $this['route'] = function ($app) {
            return trim($app['request']->getPathInfo(), '/');
        };

        $this['pageCache'] = function ($app) {
            return Cache\CacheFactory::create('page', $app['config']);
        };

        $this['dataCache'] = function ($app) {
            return Cache\CacheFactory::create('data', $app['config']);
        };

        $this['events'] = function ($app) {
            return new EventDispatcher();
        };

        $this['plugins'] = function ($app) {
            return new Plugins($app);
        };

        $this['twig'] = function ($app) {
            return new Twig($app);
        };

        $this['menu'] = function ($app) {
            $builder = new Menu\PageMenuCollectionBuilder($app);
            return $builder->build();
        };

        $this['tree'] = function ($app) {
            $builder = new Menu\PageMenuTreeBuilder();
            return $builder->build($app['menu']);
        };

        $this['posts'] = function ($app) {
            $builder = new Menu\PostMenuCollectionBuilder($app);
            return $builder->build();
        };

        $this['paginator'] = function ($app) {
            return new Paginator($app['posts'], $app['request']);
        };

        $this['rootPath'] = function ($app) {
            return new Menu\PageRootPath($app['menu'], $app['route']);
        };

        $this['data'] = function ($app) {
            $loader = new Loader\DataLoader($app['config']->get('data.extensions'));
            return $loader->load($app['config']->get('data.path'));
        };

        $this['urlMatcher'] = function ($app) {
            return new Url\UrlMatcher($app['menu'], $app['posts']);
        };

        $this['urlGenerator'] = function ($app) {
            return new Url\UrlGenerator($app['request'], $app['config']->get('nice_urls', false));
        };

        $this['page'] = function ($app) {
            return new Page(); // be sure that we always have a Page object
        };

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
    }

    /**
     * @return void
     */
    public function run()
    {
        $this['plugins']->init();

        $this->fireEvent('onPluginsInitialized', ['plugins' => $this['plugins']]);

        $this['twig']->init();

        $this->fireEvent('onTwigInitialized', ['twig' => $this['twig']->environment]);

        try {

            $response = $this->handle();

        } catch (ResourceNotFoundException $e) {

            $content = $this['twig']->render('error.html', ['error' => $e]);
            $response = new Response($content, 404);

        } catch (\Exception $e) {

            $content = $this['twig']->render('error.html', ['error' => $e]);
            $response = new Response($content, 500);

        }

        $this->fireEvent('onOutputGenerated', ['response' => $response]);

        $response->send();

        $this->fireEvent('onOutputRendered');
    }

    /**
     * @return Response
     */
    protected function handle()
    {
        $path = $this['urlMatcher']->match($this['route']);

        $pageLoader = new Loader\PageLoader();
        $this['page'] = $pageLoader->load($path);

        $this->fireEvent('onPageLoaded', ['page' => $this['page']]);

        $content = $this['pageCache']->get($path);
        if ($content === false) {
            $layout = $this['page']->getLayout();
            if (empty($layout)) {
                $content = $this->renderContentSegment(0);
            } else {
                $content = $this['twig']->render($layout);
            }
            $this['pageCache']->set($path, $content);
        }

        $response = new Response($content);
        $response->headers->set('Content-Type', $this['page']->getContentType());
        return $response;
    }

    /**
     * @param string|int $segmentId
     * @return string
     */
    public function renderContentSegment($segmentId)
    {
        $segment = $this['page']->getSegment($segmentId);

        $this->fireEvent('onContentSegmentLoaded', ['segment' => &$segment]);

        $twigged = $this['twig']->render($segment);

        $this->fireEvent('onContentSegmentRendered', ['segment' => &$twigged]);

        $formatter = Formatter\FormatterFactory::create($this['page']->getFormat());
        return $formatter->transform($twigged);
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this['route'];
    }

    /**
     * @param  string $eventName
     * @param  array  $attributes
     * @return Event
     */
    protected function fireEvent($eventName, array $attributes = [])
    {
        if (!isset($attributes['app'])) {
            $attributes['app'] = $this;
        }
        return $this['events']->dispatch($eventName, new Event($attributes));
    }
}
