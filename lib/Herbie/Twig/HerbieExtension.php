<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Twig;

use DateTime;
use Herbie\Site;
use Herbie\Formatter;
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
        foreach ($htmlOptions as $key => $value) {
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
            new Twig_SimpleFilter('markup', array($this, 'filterMarkup'), ['is_safe' => ['html']]),
            new Twig_SimpleFilter('strftime', array($this, 'filterStrftime')),
            new Twig_SimpleFilter('textile', array($this, 'filterTextile'), ['is_safe' => ['html']])
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
            new Twig_SimpleFunction('disqus', array($this, 'functionDisqus'), $options),
            new Twig_SimpleFunction('googleMaps', array($this, 'functionGoogleMaps'), $options),
            new Twig_SimpleFunction('image', array($this, 'functionImage'), $options),
            new Twig_SimpleFunction('isPage', array($this, 'functionIsPage'), $options),
            new Twig_SimpleFunction('isPost', array($this, 'functionIsPost'), $options),
            new Twig_SimpleFunction('link', array($this, 'functionLink'), $options),
            new Twig_SimpleFunction('menu', array($this, 'functionMenu'), $options),
            new Twig_SimpleFunction('pageTitle', array($this, 'functionPageTitle'), $options),
            new Twig_SimpleFunction('sitemap', array($this, 'functionSitemap'), $options),
            new Twig_SimpleFunction('url', array($this, 'functionUrl'), $options),
            new Twig_SimpleFunction('vimeo', array($this, 'functionVimeo'), $options),
            new Twig_SimpleFunction('youTube', array($this, 'functionYouTube'), $options),
        ];
    }

    /**
     * @return array
     */
    public function getTests()
    {
        return [];
    }

    /**
     * @param MenuTree $tree
     * @param bool $showHidden
     * @return string
     */
    protected function traversTree($tree, $showHidden)
    {
        static $route = null;

        if (is_null($route)) {
            $route = trim($this->app->getRoute(), '/');
        }

        $html = '<ul>';
        foreach ($tree as $item) {
            if (!$showHidden && $item->hidden) {
                continue;
            }
            if (($item->getRoute() == $route) || ($item->getRoute() == $route . '/index')) {
                $html .= '<li class="active">';
            } else {
                $html .= '<li>';
            }
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
     * @param string $content
     * @return string
     */
    public function filterMarkup($content)
    {
        $formatter = Formatter\FormatterFactory::create('markup');
        return $formatter->transform($content);
    }

    /**
     * @param string $date
     * @param string $format
     * @return string
     */
    public function filterStrftime($date, $format = '%x')
    {
        $dateTime = new DateTime($date);
        return strftime($format, $dateTime->getTimestamp());
    }

    /**
     * @param string $content
     * @return string
     */
    public function filterTextile($content)
    {
        $formatter = Formatter\FormatterFactory::create('textile');
        return $formatter->transform($content);
    }

    /**
     * @param string $route
     * @return string
     */
    public function functionAbsUrl($route)
    {
        return $this->app['urlGenerator']->generateAbsolute($route);
    }

    /**
     * @return string
     */
    public function functionBodyClass()
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
    public function functionBreadcrumb(array $options = [])
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

        foreach ($this->app['rootPath'] as $item) {
            $links[] = $this->createLink($item->getRoute(), $item->getTitle());
        }

        if (!empty($reverse)) {
            $links = array_reverse($links);
        }

        $html = '<ul class="breadcrumb">';
        foreach ($links as $link) {
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
    public function functionContent($segmentId = 0, $wrap = false)
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
     * @param string $shortname
     * @return string
     */
    public function functionDisqus($shortname)
    {
        return $this->app['twigFilesystem']->render('extension/herbie/disqus.html', array(
           'shortname' => $shortname
        ));
    }

    /**
     * @param string $id
     * @param int $width
     * @param int $height
     * @param string $type
     * @param string $class
     * @param int $zoom
     * @param string $address
     * @return string
     */
    public function functionGoogleMaps($id = 'gmap', $width = 600, $height = 450, $type = 'roadmap', $class = 'gmap', $zoom = 15, $address = '')
    {
        static $instances = 0;
        $instances++;
        return $this->app['twigFilesystem']->render('extension/herbie/google_maps.html', array(
            'id' => $id . '-' . $instances,
            'width' => $width,
            'height' => $height,
            'type' => $type,
            'class' => $class,
            'zoom' => $zoom,
            'address' => $address,
            'instances' => $instances
        ));
    }

    /**
     * @param string $src
     * @param int $width
     * @param int $height
     * @param string $alt
     * @param string $class
     * @return string
     */
    public function functionImage($src, $width = 0, $height = 0, $alt = '', $class = '')
    {
        $attribs = array();
        $attribs['src'] = $this->app['request']->getBasePath() . '/' . $src;
        $attribs['alt'] = $alt;
        if (!empty($width)) {
            $attribs['width'] = $width;
        }
        if (!empty($height)) {
            $attribs['height'] = $height;
        }
        if (!empty($class)) {
            $attribs['class'] = $class;
        }
        return sprintf('<img %s>', $this->buildHtmlAttributes($attribs));
    }

    /**
     * @return boolean
     */
    public function functionIsPage()
    {
        return !$this->functionIsPost();
    }

    /**
     * @return boolean
     */
    public function functionIsPost()
    {
        $postsPath = $this->app['config']['posts']['path'];
        $pagePath = $this->app['page']->getPath();
        $pos = strpos($pagePath, $postsPath);
        return $pos === 0;
    }

    /**
     * @param string $route
     * @param string $label
     * @param array $htmlAttributes
     * @return string
     */
    public function functionLink($route, $label, $htmlAttributes = [])
    {
        return $this->createLink($route, $label, $htmlAttributes);
    }

    /**
     * @param array $options
     * @return string
     */
    public function functionMenu(array $options = [])
    {
        extract($options); // showHidden, route
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
    public function functionPagetitle(array $options = [])
    {
        extract($options); // delim, siteTite, rootTitle, reverse

        $delim = isset($delim) ? $delim : ' / ';
        $siteTitle = isset($siteTitle) ? $siteTitle : null;
        $rootTitle = isset($rootTitle) ? $rootTitle : null;
        $reverse = isset($reverse) ? (bool) $reverse : false;

        $count = count($this->app['rootPath']);

        $titles = [];

        if (!empty($siteTitle)) {
            $titles[] = $siteTitle;
        }

        foreach ($this->app['rootPath'] as $item) {
            if ((1 == $count) && $item->isStartPage() && !empty($rootTitle)) {
                return $rootTitle;
            }
            $titles[] = $item->getTitle();
        }

        if ($this->functionIsPost()) {
            $titles[] = $this->app['page']->getTitle();
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
    public function functionSitemap(array $options = [])
    {
        extract($options); // showHidden, route
        $showHidden = isset($showHidden) ? (bool) $showHidden : false;
        $route = isset($route) ? $route : null;

        $tree = empty($route) ? $this->app['tree'] : $this->app['tree']->findByRoute($route);

        $html = $this->traversTree($tree, $showHidden);
        return sprintf('<div class="sitemap">%s</div>', $html);
    }

    /**
     * @param string $route
     * @return string
     */
    public function functionUrl($route)
    {
        return $this->app['urlGenerator']->generate($route);
    }

    /**
     * @param string $id
     * @param int $width
     * @param int $height
     * @param int $responsive
     * @return string
     * @see http://embedresponsively.com/
     */
    public function functionVimeo($id, $width = 480, $height = 320, $responsive = 1)
    {
        $attribs = array(
            'src' => sprintf('//player.vimeo.com/video/%s', $id),
            'width' => $width,
            'height' => $height,
            'frameborder' => 0
        );
        $style = '';
        $class = '';
        if(!empty($responsive)) {
            $style = '<style>.video-vimeo-responsive { position: relative; padding-bottom: 56.25%; padding-top: 30px; height: 0; overflow: hidden; max-width: 100%; height: auto; } .video-vimeo-responsive iframe, .video-vimeo-responsive object, .video-vimeo-responsive embed { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }</style>';
            $class = 'video-vimeo-responsive';
        }
        return sprintf(
            '%s<div class="video video-vimeo %s"><iframe %s webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe></div>',
            $style,
            $class,
            $this->buildHtmlAttributes($attribs)
        );
    }

    /**
     * @param string $id
     * @param int $width
     * @param int $height
     * @param int $responsive
     * @return string
     * @see http://embedresponsively.com/
     */
    public function functionYouTube($id, $width = 480, $height = 320, $responsive = 1)
    {
        $attribs = array(
            'src' => sprintf('//www.youtube.com/embed/%s?rel=0', $id),
            'width' => $width,
            'height' => $height,
            'frameborder' => 0
        );
        $style = empty($responsive) ? '' : '<style>.video-youtube { position: relative; padding-bottom: 56.25%; padding-top: 30px; height: 0; overflow: hidden; max-width: 100%; height: auto; } .video-youtube iframe, .video-youtube object, .video-youtube embed { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }</style>';
        return sprintf(
            '%s<div class="video video-youtube"><iframe %s allowfullscreen></iframe></div>',
            $style,
            $this->buildHtmlAttributes($attribs)
        );
    }
}
