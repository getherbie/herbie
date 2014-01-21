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

        $app = $this->app;
        $ext = $this;
        $dir = __DIR__;

        include(__DIR__.'/Functions/test3.php');

        return [
            new Twig_SimpleFunction('test1', include(__DIR__.'/Functions/test1.php'), ['is_safe' => ['html']]),

            include("{$dir}/Functions/Test2.php"),

            new Twig_SimpleFunction('test3', $test3, ['is_safe' => ['html']]),

            new Twig_SimpleFunction('link', function ($route, $label, $attributes = []) {
                return $this->createLink($route, $label, $attributes);
            }, ['is_safe' => ['html']]),

            new Twig_SimpleFunction('url', function ($route) {
                return $this->app['urlGenerator']->generate($route);
            }, ['is_safe' => ['html']]),

            new Twig_SimpleFunction('absurl', function ($route) {
                return $this->app['urlGenerator']->generateAbsolute($route);
            }, ['is_safe' => ['html']]),

            new Twig_SimpleFunction('breadcrumb', function (array $options=[]) use ($app) {

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

                foreach($app['rootPath'] AS $item) {
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

            }, ['is_safe' => ['html']]),

            new Twig_SimpleFunction('pagetitle', function (array $options=[]) use ($app) {

                extract($options); // delim, siteTite, rootTitle, reverse

                $delim      = isset($delim) ? $delim : ' / ';
                $siteTitle  = isset($siteTitle) ? $siteTitle : NULL;
                $rootTitle  = isset($rootTitle) ? $rootTitle : NULL;
                $reverse    = isset($reverse) ? (bool)$reverse : false;

                $count = count($app['rootPath']);

                $titles = [];

                if(!empty($siteTitle)) {
                    $titles[] = $siteTitle;
                }

                foreach($app['rootPath'] AS $item) {
                    if((1==$count) && $item->isStartPage() && !empty($rootTitle)) {
                        return $rootTitle;
                    }
                    $titles[] = $item->getTitle();
                }

                if(!empty($reverse)) {
                    $titles = array_reverse($titles);
                }

                return implode($delim, $titles);

            }, ['is_safe' => ['html']]),

            new Twig_SimpleFunction('image', function ($src, $width = '', $height = '', $alt = '', $class = "") {
                return sprintf('<img src="%s" width="%d" height="%d" alt="%s" class="%s">', $src, $width, $height, $alt, $class);
            }, ['is_safe' => ['html']]),

            new Twig_SimpleFunction('content', function ($segmentId = 0) use ($app) {

                if($this->environment->getLoader() instanceof Twig_Loader_String) {
                    return $this->renderError('You can not use {{ content() }} in page files.');
                }

                $page = $app['page'];
                $segment = $page->getSegment($segmentId);

                if(isset($app['config']['pseudo_html'])) {
                    $pseudoHtml = $app['config']['pseudo_html'];
                    $segment = str_replace(
                        explode('|', $pseudoHtml['from']),
                        explode('|', $pseudoHtml['to']),
                        $segment
                    );
                }

                $twigged = $app->renderString($segment);

                $formatter = FormatterFactory::create($page->getType());
                $transformed = $formatter->transform($twigged);

                return sprintf('<div class="placeholder-%s">%s</div>', $segmentId, $transformed);

            }, ['is_safe' => ['html']]),

            new Twig_SimpleFunction('menu', function (array $options=[]) use ($app, $ext) {

                extract($options); // showHidden
                $showHidden = isset($showHidden) ? (bool)$showHidden : false;
                $route      = isset($route) ? $route : null;

                $tree = empty($route) ? $app['tree'] : $app['tree']->findByRoute($route);

            	$html = $ext->traversTree($tree, $showHidden);
                return sprintf('<div class="menu">%s</div>', $html);
            }, ['is_safe' => ['html']]),

            new Twig_SimpleFunction('sitemap', function (array $options=[]) use ($app, $ext) {

                extract($options); // showHidden
                $showHidden = isset($showHidden) ? (bool)$showHidden : false;

            	$html = $ext->traversTree($app['tree'], $showHidden);

                return sprintf('<div class="sitemap">%s</div>', $html);
			}, ['is_safe' => ['html']]),

            new Twig_SimpleFunction('bodyClass', function () use ($app) {
                $route = trim($app->getRoute(), '/');
                if(empty($route)) {
                	$route = 'index';
                }
                $layout = $app['page']->getLayout(false);
                $class = sprintf('page-%s layout-%s', $route, $layout);
                return str_replace(['/', '.'], '-', $class);
			}, ['is_safe' => ['html']]),
        ];
    }

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

}