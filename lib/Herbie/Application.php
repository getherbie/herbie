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

        $config = new Config($app);

        setlocale(LC_ALL, $config->get('locale'));
        $this->charset = $config->get('charset');
        $this->language = $config->get('language');
        $this->locale = $config->get('locale');

        $this['config'] = $config;

        $this['request'] = Request::createFromGlobals();

        $this['cache.page'] = function ($app) {
            if ($app['config']->isEmpty('cache.page.enable')) {
                return new Cache\DummyCache();
            }
            return new Cache\PageCache($app['config']->get('cache.page'));
        };

        $this['cache.data'] = function ($app) {
            if ($app['config']->isEmpty('cache.data.enable')) {
                return new Cache\DummyCache();
            }
            return new Cache\DataCache($app['config']->get('cache.data'));
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
            $cache = $app['cache.data'];
            $path = $app['config']->get('pages.path');
            $extensions = $app['config']->get('pages.extensions');
            $builder = new Menu\MenuCollectionBuilder($cache, $extensions);
            $menu = $builder->build($path);
            $this->fireEvent('onPageMenuInitialized');
            return $menu;
        };

        $this['tree'] = function ($app) {
            $builder = new Menu\MenuTreeBuilder();
            return $builder->build($app['menu']);
        };

        $this['posts'] = function ($app) {
            $cache = $app['cache.data'];
            $path = $app['config']->get('posts.path');
            $options = [
                'extensions' => $app['config']->get('posts.extensions'),
                'blogRoute' => $app['config']->get('posts.blogRoute')
            ];
            $builder = new Menu\PostCollectionBuilder($cache, $options);
            $this->fireEvent('onPostMenuInitialized');
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

        $this['shortcode'] = function ($app) {
            $tags = $app['config']->get('shortcodes', []);
            return new Shortcode($tags);
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

        $this->fireEvent('onPluginsInitialized');

        $this['twig']->init();

        $this->fireEvent('onTwigInitialized');

        try {

            $this->response = $this->handle();

        } catch (ResourceNotFoundException $e) {

            $content = $this['twig']->render('error.html', ['error' => $e]);
            $this->response = new Response($content, 404);

        } catch (Exception $e) {

            $content = $this['twig']->render('error.html', ['error' => $e]);
            $this->response = new Response($content, 500);

        }

        $this->fireEvent('onOutputGenerated');

        $this->response->send();

        $this->fireEvent('onOutputRendered');
    }

    /**
     * @return Response
     */
    public function handle()
    {
        $route = $this->getRoute();
        $path = $this['urlMatcher']->match($route);

        $pageLoader = new Loader\PageLoader();
        $this['page'] = $pageLoader->load($path);

        $this->fireEvent('onPageLoaded');

        $content = $this['cache.page']->get($path);
        if ($content === false) {
            $layout = $this['page']->getLayout();
            if (empty($layout)) {
                $content = $this->renderContentSegment(0);
            } else {
                $content = $this['twig']->render($layout);
            }
            $this['cache.page']->set($path, $content);
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

        $segment = $this['shortcode']->parse($segment);

        $twigged = $this['twig']->render($segment);

        $formatter = Formatter\FormatterFactory::create($this['page']->getFormat());
        return $formatter->transform($twigged);
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return trim($this['request']->getPathInfo(), '/');
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
