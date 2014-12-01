<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\plugin\simplesearch;

use Herbie;
use Herbie\Loader\FrontMatterLoader;
use Herbie\Menu\Page\Item;
use Twig_SimpleFunction;

class SimplesearchPlugin extends Herbie\Plugin
{
    /**
     * @param Herbie\Event $event
     */
    public function onTwigInitialized(Herbie\Event $event)
    {
        $event['twig']->addFunction(
            new Twig_SimpleFunction('simplesearch_results', [$this, 'results'], ['is_safe' => ['html']])
        );
        $event['twig']->addFunction(
            new Twig_SimpleFunction('simplesearch_form', [$this, 'form'], ['is_safe' => ['html']])
        );
    }

    /**
     * @return string
     */
    public function form()
    {
        $template = $this->config(
            'plugins.simplesearch.template.form',
            '@plugin/simplesearch/templates/form.twig'
        );
        return $this->app['twig']->render($template, [
            'action' => 'suche',
            'query' => isset($_GET['query']) ? $_GET['query'] : '',
        ]);
    }

    /**
     * @param string $shortname
     * @return string
     */
    public function results()
    {
        $query = isset($_GET['query']) ? $_GET['query'] : '';
        $results = empty($query) ? [] : $this->search($query);
        $template = $this->config(
            'plugins.simplesearch.template.results',
            '@plugin/simplesearch/templates/results.twig'
        );
        return $this->app['twig']->render($template, [
            'query' => $query,
            'results' => $results,
            'submitted' => isset($_GET['query'])
        ]);
    }

    /**
     * @param Herbie\Event $event
     */
    public function onPluginsInitialized(Herbie\Event $event)
    {
        $alias = $this->config(
            'plugins.simplesearch.pages.search',
            '@plugin/simplesearch/pages/search.html'
        );
        $path = $this->app['alias']->get($alias);
        $loader = new FrontMatterLoader();
        $item = $loader->load($path);
        $item['path'] = $alias;
        $event['app']['menu']->addItem(
            new Item($item)
        );
    }

    /**
     * @param $query
     * @return array
     */
    protected function search($query)
    {
        $i = 1;
        $max = 100;
        $results = [];

        $pageLoader = clone($this->app['pageLoader']);
        $pageLoader->unsetTwig();

        // pages
        foreach($this->app['menu'] as $item) {
            if($i>$max) continue;
            $page = $pageLoader->load($item->path);
            if($this->match($query, $page)) {
                $results[] = $item;
                $i++;
            }
        }
        // posts
        foreach($this->app['posts'] as $item) {
            if($i>$max) continue;
            $page = $pageLoader->load($item->path);
            if($this->match($query, $page)) {
                $results[] = $item;
                $i++;
            }
        }
        return $results;
    }

    /**
     * @param string $query
     * @param array $page
     * @return bool
     */
    protected function match($query, array $page)
    {
        if(!empty($page) && isset($page['segments']) && isset($page['data']['title'])) {
            if (stripos(implode('', $page['segments']), $query) !== false || stripos($page['data']['title'], $query) !== false) {
                return true;
            }
        }
        return false;
    }

}
