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
use Herbie\Menu;
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
            'plugins.config.simplesearch.template.form',
            '@plugin/simplesearch/templates/form.twig'
        );
        return $this->app['twig']->render($template, [
            'action' => 'suche',
            'query' => $this->app['request']->get('query'),
        ]);
    }

    /**
     * @param string $shortname
     * @return string
     */
    public function results()
    {
        $query = $this->app['request']->get('query');
        $results = $this->search($query);
        $template = $this->config(
            'plugins.config.simplesearch.template.results',
            '@plugin/simplesearch/templates/results.twig'
        );
        return $this->app['twig']->render($template, [
            'query' => $query,
            'results' => $results,
            'submitted' => isset($query)
        ]);
    }

    /**
     * @param Herbie\Event $event
     */
    public function onPluginsInitialized(Herbie\Event $event)
    {
        $alias = $this->getPathAlias();
        $path = $this->app['alias']->get($alias);
        $loader = new FrontMatterLoader();
        $item = $loader->load($path);
        $item['path'] = $alias;
        $event['app']['menu']->addItem(
            new Menu\Page\Item($item)
        );
    }

    /**
     * @param Menu\ItemInterface $item
     * @param bool $usePageCache
     * @return array
     */
    protected function loadPageData(Menu\ItemInterface $item, $usePageCache)
    {
        if(!$usePageCache) {
            $page = $this->app['pageLoader']->load($item->path, false);
            $title = isset($page['data']['title']) ? $page['data']['title'] : '';
            $content = $page['segments'] ? implode('', $page['segments']) : '';
            return [$title, $content];
        }

        $content = $this->app['pageCache']->get($item->path);
        if ($content !== false) {
            return [strip_tags($content)];
        }

        return [];
    }

    /**
     * @param $query
     * @return array
     */
    protected function search($query)
    {
        if(empty($query)) {
            return [];
        }

        $i = 1;
        $max = 100;
        $results = [];

        $pathAlias = $this->getPathAlias();
        $usePageCache = $this->config('cache.page.enable', false);
        $usePageCache &= $this->config('plugins.config.simplesearch.use_page_cache', false);

        $appendIterator = new \AppendIterator();
        $appendIterator->append($this->app['menu']->getIterator());
        $appendIterator->append($this->app['posts']->getIterator());

        foreach($appendIterator as $item) {
            if($i>$max || empty($item->title) || $item->path == $pathAlias) {
                continue;
            }
            $data = $this->loadPageData($item, $usePageCache);
            if($this->match($query, $data)) {
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
    protected function match($query, array $data)
    {
        foreach($data as $part) {
            if(empty($part)) {
                continue;
            }
            if (stripos($part, $query) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    protected function getPathAlias()
    {
        return $this->config(
            'plugins.config.simplesearch.page.search',
            '@plugin/simplesearch/pages/search.html'
        );
    }

}
