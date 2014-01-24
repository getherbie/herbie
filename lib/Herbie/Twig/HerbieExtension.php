<?php

/*
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Twig;

use Herbie\Formatter\FormatterFactory;
use Herbie\Site;
use Twig;
use Twig_Environment;
use Twig_Extension;
use Twig_Loader_String;
use Twig_SimpleFunction;

class HerbieExtension extends Twig_Extension
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Twig_Environment
     */
    private $environment;

    /**
     * @param Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * @param Twig_Environment $environment
     */
    public function initRuntime(Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'herbie';
    }

    /**
     * @return array
     */
    public function getGlobals()
    {
        return [
            'site' => new Site($this->app),
            'page' => $this->app['page'],
            'text' => new Text()
        ];
    }

    /**
     * @return array
     */
    public function getTokenParsers()
    {
        return [
            new HighlightTokenParser()
        ];
    }

    /**
     * @param array $htmlOptions
     * @return string
     */
    protected function buildHtmlAttributes($htmlOptions = [])
    {
        $attributes = '';
        foreach ($htmlOptions AS $key => $value) {
            $attributes .= $key . '="' . $value . '" ';
        }
        return trim($attributes);
    }

    /**
     * @param string $route
     * @param string $label
     * @param array $attributes
     * @return string
     */
    public function createLink($route, $label, $attributes = [])
    {
        $url = $this->app['urlGenerator']->generate($route);
        $attributesAsString = $this->buildHtmlAttributes($attributes);
        return sprintf('<a href="%s"%s>%s</a>', $url, $attributesAsString, $label);
    }

    /**
     * @param string $message
     * @return string
     */
    protected function renderError($message)
    {
        $style = 'background:red;color:white;padding:4px;margin:2em 0';
        $message = 'Error: ' . $message;
        return sprintf('<div style="%s">%s</div>', $style, $message);
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        $options = ['is_safe' => ['html']];
        return [
            new Twig_SimpleFunction('absurl', array($this, 'functionAbsurl'), $options),
            new Twig_SimpleFunction('bodyClass', array($this, 'functionBodyClass'), $options),
            new Twig_SimpleFunction('breadcrumb', array($this, 'functionBreadcrumb'), $options),
            new Twig_SimpleFunction('content', array($this, 'functionContent'), $options),
            new Twig_SimpleFunction('image', array($this, 'functionImage'), $options),
            new Twig_SimpleFunction('link', array($this, 'functionLink'), $options),
            new Twig_SimpleFunction('menu', array($this, 'functionMenu'), $options),
            new Twig_SimpleFunction('pagetitle', array($this, 'functionPageTitle'), $options),
            new Twig_SimpleFunction('sitemap', array($this, 'functionSitemap'), $options),
            new Twig_SimpleFunction('url', array($this, 'functionUrl'), $options),
        ];
    }

    /**
     * @param MenuTree $tree
     * @param bool $showHidden
     * @return string
     */
    public function traversTree($tree, $showHidden)
    {
        $html = '<ul>';
        foreach ($tree AS $item) {
            if (!$showHidden && $item->hidden) {
                continue;
            }
            $html .= '<li>';
            $html .= $this->createLink($item->getRoute(), $item->getTitle());
            if($showHidden && $item->hasItems()) {
                $html .= $this->traversTree($item->getItems(), $showHidden);
            } elseif ($item->hasVisibleItems()) {
                $html .= $this->traversTree($item->getItems(), $showHidden);
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     * @param string $route
     * @return string
     */
    public function functionAbsurl($route) {
        return $this->app['urlGenerator']->generateAbsolute($route);
    }

    /**
     * @return string
     */
    public function functionBodyClass()
    {
        $route = trim($this->app->getRoute(), '/');
        if(empty($route)) {
            $route = 'index';
        }
        $layout = $this->app['page']->getLayout(false);
        $class = sprintf('page-%s layout-%s', $route, $layout);
        return str_replace(['/', '.'], '-', $class);
    }

    /**
     * @param array $options
     * @return string
     */
    public function functionBreadcrumb(array $options=[]){

        // Options
        extract($options);
        $delim      = isset($delim) ? $delim : '';
        $homeLink   = isset($homeLink) ? $homeLink : null;
        $reverse    = isset($reverse) ? (bool)$reverse : false;

        $links = [];

        if(!empty($homeLink)) {
            if(is_array($homeLink)) {
                $route = reset($homeLink);
                $label = isset($homeLink[1]) ? $homeLink[1] : 'Home';
            } else {
                $route = $homeLink;
                $label = 'Home';
            }
            $links[] = $this->createLink($route, $label);
        }

        foreach($this->app['rootPath'] AS $item) {
            $links[] = $this->createLink($item->getRoute(), $item->getTitle());
        }

        if(!empty($reverse)) {
            $links = array_reverse($links);
        }

        $html = '<ul class="breadcrumb">';
        foreach($links AS $link) {
            $html .= '<li>' . $link . '</li>';
        }
        $html .= '</ul>';

        return $html;

    }

    /**
     * @param string|int $segmentId
     * @return string
     */
    public function functionContent($segmentId = 0) {

        if($this->environment->getLoader() instanceof Twig_Loader_String) {
            return $this->renderError('You can not use {{ content() }} in page files.');
        }

        $page = $this->app['page'];
        $segment = $page->getSegment($segmentId);

        if(isset($this->app['config']['pseudo_html'])) {
            $pseudoHtml = $this->app['config']['pseudo_html'];
            $segment = str_replace(
                explode('|', $pseudoHtml['from']),
                explode('|', $pseudoHtml['to']),
                $segment
            );
        }

        $twigged = $this->app->renderString($segment);

        $formatter = FormatterFactory::create($page->getType());
        $transformed = $formatter->transform($twigged);

        return sprintf('<div class="placeholder-%s">%s</div>', $segmentId, $transformed);

    }

    /**
     * @param string $src
     * @param int $width
     * @param int $height
     * @param string $alt
     * @param string $class
     * @return string
     */
    public function functionImage($src, $width = '', $height = '', $alt = '', $class = "") {
        return sprintf('<img src="%s" width="%d" height="%d" alt="%s" class="%s">', $src, $width, $height, $alt, $class);
    }

    /**
     * @param string $route
     * @param string $label
     * @param array $attributes
     * @return string
     */
    public function functionLink($route, $label, $attributes = []) {
        return $this->createLink($route, $label, $attributes);
    }

    /**
     * @param array $options
     * @return string
     */
    public function functionMenu(array $options=[])
    {
        extract($options); // showHidden
        $showHidden = isset($showHidden) ? (bool)$showHidden : false;
        $route      = isset($route) ? $route : null;

        $tree = empty($route) ? $this->app['tree'] : $this->app['tree']->findByRoute($route);

        $html = $this->traversTree($tree, $showHidden);
        return sprintf('<div class="menu">%s</div>', $html);
    }

    /**
     * @param array $options
     * @return string
     */
    public function functionPagetitle (array $options=[])
    {

        extract($options); // delim, siteTite, rootTitle, reverse

        $delim      = isset($delim) ? $delim : ' / ';
        $siteTitle  = isset($siteTitle) ? $siteTitle : NULL;
        $rootTitle  = isset($rootTitle) ? $rootTitle : NULL;
        $reverse    = isset($reverse) ? (bool)$reverse : false;

        $count = count($this->app['rootPath']);

        $titles = [];

        if(!empty($siteTitle)) {
            $titles[] = $siteTitle;
        }

        foreach($this->app['rootPath'] AS $item) {
            if((1==$count) && $item->isStartPage() && !empty($rootTitle)) {
                return $rootTitle;
            }
            $titles[] = $item->getTitle();
        }

        if(!empty($reverse)) {
            $titles = array_reverse($titles);
        }

        return implode($delim, $titles);

    }

    /**
     * @param array $options
     * @return string
     */
    public function functionSitemap(array $options=[]) {

        extract($options); // showHidden
        $showHidden = isset($showHidden) ? (bool)$showHidden : false;

        $html = $this->traversTree($this->app['tree'], $showHidden);

        return sprintf('<div class="sitemap">%s</div>', $html);
    }

    /**
     * @param string $route
     * @return string
     */
    public function functionUrl($route) {
        return $this->app['urlGenerator']->generate($route);
    }

}