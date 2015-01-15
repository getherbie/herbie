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
use Pimple\Container as PimpleContainer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * The application using Pimple as dependency injection container.
 */
class Container extends PimpleContainer
{

    /**
     * @param string $sitePath
     */
    public function init($sitePath, $config)
    {
        $this['appPath'] = realpath(__DIR__ . '/../../');
        $this['webPath'] = rtrim(dirname($_SERVER['SCRIPT_FILENAME']), '/');
        $this['sitePath'] = realpath($sitePath);

        $this['alias'] = new Alias([
            '@app' => rtrim($this['appPath'], '/'),
            '@asset' => rtrim($this['sitePath'], '/') . '/assets',
            '@page' => rtrim($config->get('pages.path'), '/'),
            '@plugin' => rtrim($config->get('plugins.path'), '/'),
            '@post' => rtrim($config->get('posts.path'), '/'),
            '@site' => rtrim($this['sitePath'], '/'),
            '@web' => rtrim($this['webPath'], '/')
        ]);

        setlocale(LC_ALL, $config->get('locale'));
        $this->charset = $config->get('charset');
        $this->language = $config->get('language');
        $this->locale = $config->get('locale');

        $this['config'] = $config;

        $this['request'] = Request::createFromGlobals();

        $this['route'] = function ($app) {
            return trim($app['request']->getPathInfo(), '/');
        };

        $this['parentRoutes'] = function ($app) {
            $parts = empty($app['route']) ? [] : explode('/', $app['route']);
            $route = '';
            $delim = '';
            $parentRoutes[] = ''; // root
            foreach($parts as $part) {
                $route .= $delim . $part;
                $parentRoutes[] = $route;
                $delim = '/';
            }
            return $parentRoutes;
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
            $builder = new Menu\Page\Builder($app);
            return $builder->buildCollection();
        };

        $this['pageTree'] = function ($app) {
            return Menu\Page\Node::buildTree($app['menu']);
        };

        $this['posts'] = function ($app) {
            $builder = new Menu\Post\Builder($app);
            return $builder->build();
        };

        $this['paginator'] = function ($app) {
            return new Paginator($app['posts'], $app['request']);
        };

        $this['rootPath'] = function ($app) {
            return new Menu\Page\RootPath($app['menu'], $app['route']);
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

        $this['assets'] = function ($app) {
            return new Assets($app);
        };
    }

}