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

#use herbie\plugin\simplesearch\classes\SimplesearchExtension;
use Herbie;
use Herbie\Menu\Page\Item;
use Twig_SimpleFunction;

class SimplesearchPlugin extends Herbie\Plugin
{
    public function onTwigInitialized(Herbie\Event $event)
    {
        $event['twig']->addFunction(
            new Twig_SimpleFunction('simplesearch_results', [$this, 'results'], ['is_safe' => ['html']])
        );
        $event['twig']->addFunction(
            new Twig_SimpleFunction('simplesearch_form', [$this, 'form'], ['is_safe' => ['html']])
        );
    }

    public function form()
    {
        $template = $this->app['config']->get(
            'plugins.simplesearch.template.form',
            '@plugin/simplesearch/templates/form.twig'
        );
        return $this->app['twig']->render($template, [
            'route' => 'suche',
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

        if(empty($query)) {
            $results = [];
        } else {
            $results = $this->search($query);
        }

        $template = $this->app['config']->get(
            'plugins.simplesearch.template.results',
            '@plugin/simplesearch/templates/results.twig'
        );
        return $this->app['twig']->render($template, [
            'query' => $query,
            'results' => $results
        ]);
    }

    public function onPluginsInitialized(Herbie\Event $event)
    {
        $event['app']['menu']->addItem(
            new Item([
                'route' => 'suche',
                'title' => 'Suche',
                'path' => '@plugin/simplesearch/pages/search.html',
                'noCache' => 1,
                'hidden' => 0
            ])
        );
    }

    protected function search($query)
    {
        $results = [];
        $pageLoader = clone($this->app['pageLoader']);
        $pageLoader->unsetTwig();
        // pages
        foreach($this->app['menu'] as $item) {
            $page = $pageLoader->load($item->path);
            if($this->match($query, $page)) {
                $results[] = $item;
            }
        }
        // posts
        foreach($this->app['posts'] as $item) {
            $page = $pageLoader->load($item->path);
            if($this->match($query, $page)) {
                $results[] = $item;
            }
        }
        return $results;
    }

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
