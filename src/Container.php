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

use Pimple\Container as Pimple;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Container extends Pimple
{

    public function __construct($sitePath, $vendorDir, array $values = [])
    {
        parent::__construct($values);

        $request = Request::createFromGlobals();
        $config = new Config($sitePath, dirname($_SERVER['SCRIPT_FILENAME']), $request->getBaseUrl());

        $this['Alias'] = new Alias([
            '@app' => $config->get('app.path'),
            '@asset' => $sitePath . '/assets',
            '@media' => $config->get('media.path'),
            '@page' => $config->get('pages.path'),
            '@plugin' => $config->get('plugins.path'),
            '@post' => $config->get('posts.path'),
            '@site' => $sitePath,
            '@vendor' => $vendorDir,
            '@web' => $config->get('web.path')
        ]);

        $this['Assets'] = function ($c) {
            return new Assets($c['Alias'], $c['Config']->get('web.url'));
        };

        $this['Cache\PageCache'] = function ($c) {
            return Cache\CacheFactory::create('page', $c['Config']);
        };

        $this['Cache\DataCache'] = function ($c) {
            return Cache\CacheFactory::create('data', $c['Config']);
        };

        $this['Config'] = $config;

        $this['DataArray'] = function ($c) {
            $loader = new Loader\DataLoader($c['Config']->get('data.extensions'));
            return $loader->load($c['Config']->get('data.path'));
        };

        $this['EventDispatcher'] = function () {
            return new EventDispatcher();
        };

        $this['Loader\PageLoader'] = function ($c) {
            $loader = new Loader\PageLoader($c['Alias']);
            return $loader;
        };

        $this['Menu\Page\Builder'] = function ($c) {

            $paths = [];
            $paths['@page'] = realpath($c['Config']->get('pages.path'));
            foreach ($c['Config']->get('pages.extra_paths', []) as $alias) {
                $paths[$alias] = $c['Alias']->get($alias);
            }
            $extensions = $c['Config']->get('pages.extensions', []);

            $builder = new Menu\Page\Builder($paths, $extensions);
            return $builder;
        };

        $this['Menu\Item'] = function ($c) {
            return $c['Url\UrlMatcher']->match($c['Request']->getRoute());
        };

        $this['Menu\Page\Collection'] = function ($c) {
            $c['Menu\Page\Builder']->setCache($c['Cache\DataCache']);
            return $c['Menu\Page\Builder']->buildCollection();
        };

        $this['Menu\Page\Node'] = function ($c) {
            return Menu\Page\Node::buildTree($c['Menu\Page\Collection']);
        };

        $this['Menu\Page\RootPath'] = function ($c) {
            return new Menu\Page\RootPath($c['Menu\Page\Collection'], $c['Request']->getRoute());
        };

        $this['Menu\Post\Collection'] = function ($c) {
            $builder = new Menu\Post\Builder($c['Cache\DataCache'], $c['Config']);
            return $builder->build();
        };

        $this['Page'] = function ($c) {

            if (!$c['Twig']->isInitialized()) {
                throw new \Exception('You have to initialize Twig before using Page.');
            }

            $menuItem = $c['Menu\Item'];

            $path = $menuItem->getPath();

            $page = false;

            // @todo Implement a proper page cache
            // get content from cache if cache enabled
            if (empty($menuItem->nocache)) {
                $page = $c['Cache\PageCache']->get($path);
            }

            if (false === $page) {

                $page = new Page();
                $page->setLoader($c['Loader\PageLoader']);
                $page->load($path);

                Application::fireEvent('onPageLoaded', ['page' => $page]);

                if (empty($menuItem->nocache)) {
                    $c['Cache\PageCache']->set($path, $page);
                }
            }

            return $page;

        };

        $this['Plugins'] = function () {
            return new Plugins();
        };

        $this['Request'] = $request;

        $this['Translator'] = function ($c) {
            if (!$c['Plugins']->isInitialized()) {
                throw new \Exception('You have to initialize Plugins before using Translator.');
            }
            $translator = new Translator($c['Config']->get('language'), ['app' => $c['Alias']->get('@app/messages')]);
            foreach ($c['Plugins']->getDirectories() as $key => $dir) {
                $translator->addPath($key, $dir . '/messages');
            }
            $translator->init();
            return $translator;
        };

        $this['Twig'] = function ($c) {
            if (!$c['Plugins']->isInitialized()) {
                throw new \Exception('You have to initialize Plugins before using Twig.');
            }
            return new Twig($c['Config']);
        };

        $this['Url\UrlGenerator'] = function ($c) {
            return new Url\UrlGenerator($c['Request'], $c['Config']->get('nice_urls', false));
        };

        $this['Url\UrlMatcher'] = function ($c) {
            return new Url\UrlMatcher($c['Menu\Page\Collection'], $c['Menu\Post\Collection']);
        };

    }

}
