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

use DateTime;
use Herbie\Formatter\FormatterFactory;
use Herbie\Site;
use Twig;
use Twig_Environment;
use Twig_Extension;
use Twig_Loader_String;
use Twig_SimpleFilter;
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
     * @param array $htmlAttributes
     * @return string
     */
    protected function createLink($route, $label, $htmlAttributes = [])
    {
        $url = $this->app['urlGenerator']->generate($route);
        $attributesAsString = $this->buildHtmlAttributes($htmlAttributes);
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
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('strftime', array($this, 'filterStrftime'))
        ];
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        $options = ['is_safe' => ['html']];
        return [
            new Twig_SimpleFunction('absUrl', array($this, 'functionAbsUrl'), $options),
            new Twig_SimpleFunction('bodyClass', array($this, 'functionBodyClass'), $options),
            new Twig_SimpleFunction('breadcrumb', array($this, 'functionBreadcrumb'), $options),
            new Twig_SimpleFunction('content', array($this, 'functionContent'), $options),
            new Twig_SimpleFunction('image', array($this, 'functionImage'), $options),
            new Twig_SimpleFunction('link', array($this, 'functionLink'), $options),
            new Twig_SimpleFunction('menu', array($this, 'functionMenu'), $options),
            new Twig_SimpleFunction('pageTitle', array($this, 'functionPageTitle'), $options),
            new Twig_SimpleFunction('sitemap', array($this, 'functionSitemap'), $options),
            new Twig_SimpleFunction('url', array($this, 'functionUrl'), $options),
        ];
    }

    /**
     * @param MenuTree $tree
     * @param bool $showHidden
     * @return string
     */
    protected function traversTree($tree, $showHidden)
    {
        $html = '<ul>';
        foreach ($tree AS $item) {
            if (!$showHidden && $item->hidden) {
                continue;
            }
            $html .= '<li>';
            $html .= $this->createLink($item->getRoute(), $item->getTitle());
            if ($showHidden && $item->hasItems()) {
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
     * @param string $date
     * @param string $format
     * @return string
     */
    protected function filterStrftime($date, $format = '%x')
    {
        $dateTime = new DateTime($date);
        return strftime($format, $dateTime->getTimestamp());
    }

    /**
     * @param string $route
     * @return string
     */
    protected function functionAbsUrl($route)
    {
        return $this->app['urlGenerator']->generateAbsolute($route);
    }

    /**
     * @return string
     */
    protected function functionBodyClass()
    {
        $route = trim($this->app->getRoute(), '/');
        if (empty($route)) {
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
    protected function functionBreadcrumb(array $options = [])
    {
        // Options
        extract($options);
        $delim = isset($delim) ? $delim : '';
        $homeLink = isset($homeLink) ? $homeLink : null;
        $reverse = isset($reverse) ? (bool) $reverse : false;

        $links = [];

        if (!empty($homeLink)) {
            if (is_array($homeLink)) {
                $route = reset($homeLink);
                $label = isset($homeLink[1]) ? $homeLink[1] : 'Home';
            } else {
                $route = $homeLink;
                $label = 'Home';
            }
            $links[] = $this->createLink($route, $label);
        }

        foreach ($this->app['rootPath'] AS $item) {
            $links[] = $this->createLink($item->getRoute(), $item->getTitle());
        }

        if (!empty($reverse)) {
            $links = array_reverse($links);
        }

        $html = '<ul class="breadcrumb">';
        foreach ($links AS $link) {
            $html .= '<li>' . $link . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * @param string|int $segmentId
     * @param bool $wrap
     * @return string
     */
    protected function functionContent($segmentId = 0, $wrap = false)
    {
        if ($this->environment->getLoader() instanceof Twig_Loader_String) {
            return $this->renderError('You can not use {{ content() }} in page files.');
        }
        $content = $this->app->renderContentSegment($segmentId);
        if (empty($wrap)) {
            return $content;
        }
        return sprintf('<div class="placeholder-%s">%s</div>', $segmentId, $content);
    }

    /**
     * @param string $src
     * @param int $width
     * @param int $height
     * @param string $alt
     * @param string $class
     * @return string
     */
    protected function functionImage($src, $width = '', $height = '', $alt = '', $class = '')
    {
        $attribs = array();
        $attribs['src'] = $src;
        $attribs['alt'] = $alt;
        if(!empty($width)) {
            $attribs['width'] = $width;
        }
        if(!empty($height)) {
            $attribs['height'] = $height;
        }
        if(!empty($class)) {
            $attribs['class'] = $class;
        }
        return sprintf('<img %s>', $this->buildHtmlAttributes($attribs));
    }

    /**
     * @param string $route
     * @param string $label
     * @param array $htmlAttributes
     * @return string
     */
    protected function functionLink($route, $label, $htmlAttributes = [])
    {
        return $this->createLink($route, $label, $htmlAttributes);
    }

    /**
     * @param array $options
     * @return string
     */
    protected function functionMenu(array $options = [])
    {
        extract($options); // showHidden
        $showHidden = isset($showHidden) ? (bool) $showHidden : false;
        $route = isset($route) ? $route : null;

        $tree = empty($route) ? $this->app['tree'] : $this->app['tree']->findByRoute($route);

        $html = $this->traversTree($tree, $showHidden);
        return sprintf('<div class="menu">%s</div>', $html);
    }

    /**
     * @param array $options
     * @return string
     */
    protected function functionPagetitle(array $options = [])
    {
        extract($options); // delim, siteTite, rootTitle, reverse

        $delim = isset($delim) ? $delim : ' / ';
        $siteTitle = isset($siteTitle) ? $siteTitle : NULL;
        $rootTitle = isset($rootTitle) ? $rootTitle : NULL;
        $reverse = isset($reverse) ? (bool) $reverse : false;

        $count = count($this->app['rootPath']);

        $titles = [];

        if (!empty($siteTitle)) {
            $titles[] = $siteTitle;
        }

        foreach ($this->app['rootPath'] AS $item) {
            if ((1 == $count) && $item->isStartPage() && !empty($rootTitle)) {
                return $rootTitle;
            }
            $titles[] = $item->getTitle();
        }

        if (!empty($reverse)) {
            $titles = array_reverse($titles);
        }

        return implode($delim, $titles);
    }

    /**
     * @param array $options
     * @return string
     */
    protected function functionSitemap(array $options = [])
    {
        extract($options); // showHidden
        $showHidden = isset($showHidden) ? (bool) $showHidden : false;

        $html = $this->traversTree($this->app['tree'], $showHidden);

        return sprintf('<div class="sitemap">%s</div>', $html);
    }

    /**
     * @param string $route
     * @return string
     */
    protected function functionUrl($route)
    {
        return $this->app['urlGenerator']->generate($route);
    }

}