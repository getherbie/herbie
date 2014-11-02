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

use Herbie\Formatter;
use Herbie\Menu;
use Herbie\Page;
use Herbie\Site;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Twig_Environment;
use Twig_Extension;
use Twig_Loader_String;
use Twig_SimpleFilter;
use Twig_SimpleFunction;
use Twig_SimpleTest;

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
            'page' => $this->app['page']
        ];
    }

    /**
     * @return array
     */
    /*public function getTokenParsers()
    {
        return [
            new HighlightTokenParser()
        ];
    }*/

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
            new Twig_SimpleFilter('markdown', array($this, 'filterMarkdown'), ['is_safe' => ['html']]),
            new Twig_SimpleFilter('strftime', array($this, 'filterStrftime')),
            new Twig_SimpleFilter('textile', array($this, 'filterTextile'), ['is_safe' => ['html']]),
            new Twig_SimpleFilter('visible', array($this, 'filterVisible'), ['is_safe' => ['html']]),
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
            new Twig_SimpleFunction('asciiTree', array($this, 'functionAsciiTree'), $options),
            new Twig_SimpleFunction('bodyClass', array($this, 'functionBodyClass'), $options),
            new Twig_SimpleFunction('breadcrumb', array($this, 'functionBreadcrumb'), $options),
            new Twig_SimpleFunction('content', array($this, 'functionContent'), $options),
            new Twig_SimpleFunction('image', array($this, 'functionImage'), $options),
            new Twig_SimpleFunction('link', array($this, 'functionLink'), $options),
            new Twig_SimpleFunction('menu', array($this, 'functionMenu'), $options),
            new Twig_SimpleFunction('pageTitle', array($this, 'functionPageTitle'), $options),
            new Twig_SimpleFunction('redirect', array($this, 'functionRedirect'), $options),
            new Twig_SimpleFunction('sitemap', array($this, 'functionSitemap'), $options),
            new Twig_SimpleFunction('url', array($this, 'functionUrl'), $options)
        ];
    }

    /**
     * @return array
     */
    public function getTests()
    {
        return [
            new Twig_SimpleTest('page', [$this, 'testIsPage']),
            new Twig_SimpleTest('post', [$this, 'testIsPost'])
        ];
    }

    /**
     * @param PageMenuTree $tree
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
            if ($item->getRoute() == $route) {
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
    public function filterMarkdown($content)
    {
        $formatter = Formatter\FormatterFactory::create('markdown');
        return $formatter->transform($content);
    }

    /**
     * @param string $date
     * @param string $format
     * @return string
     */
    public function filterStrftime($date, $format = '%x')
    {
        $dateTime = new \DateTime($date);
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
     * @param Menu\Page\Node $tree
     * @return Menu\Page\Iterator\FilterIterator
     */
    public function filterVisible($tree)
    {
        $treeIterator = new Menu\Page\Iterator\TreeIterator($tree);
        return new Menu\Page\Iterator\FilterIterator($treeIterator);
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
     * @param array $options
     * @return string
     */
    public function functionAsciiTree(array $options = [])
    {
        extract($options); // showHidden, route, maxDepth, class
        $showHidden = isset($showHidden) ? (bool) $showHidden : false;
        $route = isset($route) ? (string)$route : '';
        $maxDepth = isset($maxDepth) ? (int)$maxDepth : -1;
        $class = isset($class) ? (string)$class : 'sitemap';

        $branch = $this->app['pageTree']->findByRoute($route);
        $treeIterator = new Menu\Page\Iterator\TreeIterator($branch);
        $filterIterator = new Menu\Page\Iterator\FilterIterator($treeIterator);
        $filterIterator->setEnabled(!$showHidden);

        $asciiTree = new Menu\Page\Renderer\AsciiTree($filterIterator);
        $asciiTree->setMaxDepth($maxDepth);
        return $asciiTree->render();
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

        if ($this->testIsPost($this->app['page'])) {
            $titles[] = $this->app['page']->getTitle();
        }

        if (!empty($reverse)) {
            $titles = array_reverse($titles);
        }

        return implode($delim, $titles);
    }

    /**
     * @param string $route
     * @param int $status
     * @return void
     */
    public function functionRedirect($route, $status = 302)
    {
        $url = $this->app['urlGenerator']->generateAbsolute($route);
        $response = new RedirectResponse($url, $status);
        $response->send();
    }

    /**
     * @param array $options
     * @return string
     */
    public function functionSitemap(array $options = [])
    {
        extract($options); // showHidden, route, maxDepth, class
        $showHidden = isset($showHidden) ? (bool) $showHidden : false;
        $route = isset($route) ? (string)$route : '';
        $maxDepth = isset($maxDepth) ? (int)$maxDepth : -1;
        $class = isset($class) ? (string)$class : 'sitemap';

        $branch = $this->app['pageTree']->findByRoute($route);
        $treeIterator = new Menu\Page\Iterator\TreeIterator($branch);
        $filterIterator = new Menu\Page\Iterator\FilterIterator($treeIterator);
        $filterIterator->setEnabled(!$showHidden);

        $htmlTree = new Menu\Page\Renderer\HtmlTree($filterIterator, $class);
        $htmlTree->setMaxDepth($maxDepth);
        $htmlTree->itemCallback = function($node) {
            $menuItem = $node->getMenuItem();
            $href = $this->app['urlGenerator']->generate($menuItem->route);
            return sprintf('<a href="%s">%s</a>', $href, $menuItem->title);
        };
        return $htmlTree->render();
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
     * @return boolean
     */
    public function testIsPage(Page $page)
    {
        return !$this->testIsPost($page);
    }

    /**
     * @return boolean
     */
    public function testIsPost(Page $page)
    {
        $postsPath = $this->app['config']->get('posts.path');
        $pagePath = $page->getPath();
        $pos = strpos($pagePath, $postsPath);
        return $pos === 0;
    }

}