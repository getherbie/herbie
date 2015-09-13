<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\sysplugin\twig\classes;

use Herbie\Application;
use Herbie\Finder;
use Herbie\Helper;
use Herbie\Menu;
use Herbie\Page;
use Herbie\Site;
use Herbie\Http\RedirectResponse;
use Twig_Environment;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;
use Twig_SimpleTest;

class HerbieExtension extends Twig_Extension
{

    private $alias;
    private $config;
    private $request;
    private $urlGenerator;
    private $page;

    /**
     * @var Twig_Environment
     */
    private $environment;

    public function __construct()
    {
        $this->alias = Application::getService('Alias');
        $this->config = Application::getService('Config');
        $this->request = Application::getService('Request');
        $this->urlGenerator = Application::getService('Url\UrlGenerator');
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

    public function getPage()
    {
        return $this->page;
    }

    public function setPage($page)
    {
        $this->page = $page;
    }

    /**
     * @return array
     */
    public function getGlobals()
    {
        return [
            'site' => new Site(),
            'page' => $this->page
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
        $url = $this->urlGenerator->generate($route);
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
            new Twig_SimpleFilter('filesize', [$this, 'filterFilesize'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('strftime', [$this, 'filterStrftime']),
            new Twig_SimpleFilter('urlify', [$this, 'filterUrlify'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('visible', [$this, 'filterVisible'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        $options = ['is_safe' => ['html']];
        return [
            new Twig_SimpleFunction('absurl', [$this, 'functionAbsUrl'], $options),
            new Twig_SimpleFunction('addcss', [$this, 'functionAddCss'], $options),
            new Twig_SimpleFunction('addjs', [$this, 'functionAddJs'], $options),
            new Twig_SimpleFunction('config', [$this, 'functionConfig'], $options),
            new Twig_SimpleFunction('outputcss', [$this, 'functionOutputCss'], $options),
            new Twig_SimpleFunction('outputjs', [$this, 'functionOutputJs'], $options),
            new Twig_SimpleFunction('asciitree', [$this, 'functionAsciiTree'], $options),
            new Twig_SimpleFunction('bodyclass', [$this, 'functionBodyClass'], $options),
            new Twig_SimpleFunction('breadcrumb', [$this, 'functionBreadcrumb'], $options),
            new Twig_SimpleFunction('content', [$this, 'functionContent'], $options),
            new Twig_SimpleFunction('image', [$this, 'functionImage'], $options),
            new Twig_SimpleFunction('link', [$this, 'functionLink'], $options),
            new Twig_SimpleFunction('menu', [$this, 'functionMenu'], $options),
            new Twig_SimpleFunction('pagetitle', [$this, 'functionPageTitle'], $options),
            new Twig_SimpleFunction('pager', [$this, 'functionPager'], $options),
            new Twig_SimpleFunction('redirect', [$this, 'functionRedirect'], $options),
            new Twig_SimpleFunction('sitemap', [$this, 'functionSitemap'], $options),
            new Twig_SimpleFunction('translate', [$this, 'functionTranslate'], $options),
            new Twig_SimpleFunction('url', [$this, 'functionUrl'], $options),
            new Twig_SimpleFunction('mediafiles', [$this, 'functionMediafiles'], $options),
        ];
    }

    /**
     * @return array
     */
    public function getTests()
    {
        return [
            new Twig_SimpleTest('page', [$this, 'testIsPage']),
            new Twig_SimpleTest('post', [$this, 'testIsPost']),
            new Twig_SimpleTest('readable', [$this, 'testIsReadable']),
            new Twig_SimpleTest('writable', [$this, 'testIsWritable'])
        ];
    }

    /**
     * @param integer $size
     * @return string
     */
    public function filterFilesize($size)
    {
        if ($size <= 0) {
            return '0';
        }
        if ($size === 1) {
            return '1 Byte';
        }
        $mod = 1024;
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($i = 0; $size > $mod && $i < count($units) - 1; ++$i) {
            $size /= $mod;
        }
        return str_replace(',', '.', round($size, 1)) . ' ' . $units[$i];
    }

    /**
     * @param string $date
     * @param string $format
     * @return string
     */
    public function filterStrftime($date, $format = '%x')
    {
        // timestamp?
        if (is_numeric($date)) {
            $date = date('Y-m-d H:i:s', $date);
        }
        $dateTime = new \DateTime($date);
        return strftime($format, $dateTime->getTimestamp());
    }

    /**
     * Creates a web friendly URL (slug) from a string.
     *
     * @param $url
     * @return string
     */
    public function filterUrlify($url)
    {
        return Helper\StringHelper::urlify($url);
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
        return $this->urlGenerator->generateAbsolute($route);
    }

    /**
     * @param array|string $paths
     * @param array $attr
     * @param string $group
     * @param bool $raw
     * @param int $pos
     */
    public function functionAddCss($paths, $attr = [], $group = null, $raw = false, $pos = 1)
    {
        Application::getService('Assets')->addCss($paths, $attr, $group, $raw, $pos);
    }

    /**
     * @param array|string $paths
     * @param array $attr
     * @param string $group
     * @param bool $raw
     * @param int $pos
     */
    public function functionAddJs($paths, $attr = [], $group = null, $raw = false, $pos = 1)
    {
        Application::getService('Assets')->addJs($paths, $attr, $group, $raw, $pos);
    }

    /**
     * @param string $group
     * @return string
     */
    public function functionOutputCss($group = null)
    {
        return Application::getService('Assets')->outputCss($group);
    }

    /**
     * @param string $group
     * @return string
     */
    public function functionOutputJs($group = null)
    {
        return Application::getService('Assets')->outputJs($group);
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

        $branch = Application::getService('Menu\Page\Node')->findByRoute($route);
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
        $route = trim($this->request->getRoute(), '/');
        if (empty($route)) {
            $route = 'index';
        }
        $layout = $this->page->layout;
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

        foreach (Application::getService('Menu\Page\RootPath') as $item) {
            $links[] = $this->createLink($item->route, $item->getMenuTitle());
        }

        if (!empty($reverse)) {
            $links = array_reverse($links);
        }

        $html = '<ul class="breadcrumb">';
        foreach ($links as $i => $link) {
            if ($i > 0 && !empty($delim)) {
                $html .= '<li class="delim">' . $delim . '</li>';
            }
            $html .= '<li>' . $link . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * @param string $name
     * @param mixed $default
     */
    public function functionConfig($name, $default = null)
    {
        return $this->config->get($name, $default);
    }

    /**
     * @param string|int $segmentId
     * @param bool $wrap
     * @return string
     */
    public function functionContent($segmentId = 0, $wrap = false)
    {
        $page = $this->getPage();
        $content = Application::getService('Twig')->renderPageSegment($segmentId, $page);
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
        $attribs = [];
        $attribs['src'] = $this->request->getBasePath() . '/' . $src;
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
        extract($options); // showHidden, route, maxDepth, class
        $showHidden = isset($showHidden) ? (bool)$showHidden : false;
        $route = isset($route) ? (string)$route : '';
        $maxDepth = isset($maxDepth) ? (int)$maxDepth : -1;
        $class = isset($class) ? (string)$class : 'menu';

        $branch = Application::getService('Menu\Page\Node')->findByRoute($route);
        $treeIterator = new Menu\Page\Iterator\TreeIterator($branch);

        // using FilterCallback for better filtering of nested items
        $routeLine = $this->request->getRouteLine();
        $callback = [new Menu\Page\Iterator\FilterCallback($routeLine, $showHidden), 'call'];
        $filterIterator = new \RecursiveCallbackFilterIterator($treeIterator, $callback);

        $htmlTree = new Menu\Page\Renderer\HtmlTree($filterIterator);
        $htmlTree->setMaxDepth($maxDepth);
        $htmlTree->setClass($class);
        $htmlTree->itemCallback = function ($node) {
            $menuItem = $node->getMenuItem();
            $href = $this->urlGenerator->generate($menuItem->route);
            return sprintf('<a href="%s">%s</a>', $href, $menuItem->getMenuTitle());
        };
        return $htmlTree->render($this->request->getRoute());
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

        $count = count(Application::getService('Menu\Page\RootPath'));

        $titles = [];

        if (!empty($siteTitle)) {
            $titles[] = $siteTitle;
        }

        foreach (Application::getService('Menu\Page\RootPath') as $item) {
            if ((1 == $count) && $item->isStartPage() && !empty($rootTitle)) {
                return $rootTitle;
            }
            $titles[] = $item->title;
        }

        #$page = Application::getPage();
        if ($this->testIsPost($this->page)) {
            $titles[] = $this->page->title;
        }

        if (!empty($reverse)) {
            $titles = array_reverse($titles);
        }

        return implode($delim, $titles);
    }

    /**
     * @param string $limit
     * @param string $template
     * @param string $linkClass
     * @param string $nextPageLabel
     * @param string $prevPageLabel
     * @param string $prevPageIcon
     * @param string $nextPageIcon
     * @return string
     */
    public function functionPager($limit = '', $template = '{prev}{next}', $linkClass='',
        $nextPageLabel='', $prevPageLabel='', $prevPageIcon='', $nextPageIcon='')
    {
        $route = $this->request->getRoute();
        $iterator = Application::getService('Menu\Page\Collection')->getIterator();

        $prev = null;
        $cur = null;
        $next = null;
        $keys = [];
        foreach ($iterator as $i => $item) {
            if (empty($limit) || (strpos($item->route, $limit) === 0)) {
                if (isset($cur)) {
                    $next = $item;
                    break;
                }
                if ($route == $item->route) {
                    $cur = $item;
                }
                $keys[] = $i;
            }
        }

        $position = count($keys)-2;
        if ($position >= 0) {
            $iterator->seek($position);
            $prev = $iterator->current();
        }

        $replacements = [
            '{prev}' => '',
            '{next}' => ''
        ];
        $attribs = [];
        if (!empty($linkClass)) {
            $attribs['class'] = $linkClass;
        }
        if (isset($prev)) {
            $label = empty($prevPageLabel) ? $prev->getMenuTitle() : $prevPageLabel;
            $label = sprintf('<span>%s</span>', $label);
            if ($prevPageIcon) {
                $label = $prevPageIcon . $label;
            }
            $replacements['{prev}'] = $this->createLink($prev->route, $label, $attribs);
        }
        /*if(isset($cur)) {
            $label = empty($curPageLabel) ? $cur->getMenuTitle() : $curPageLabel;
            $replacements['{cur}'] = $this->createLink($cur->route, $label, $attribs);
        }*/
        if (isset($next)) {
            $label = empty($nextPageLabel) ? $next->getMenuTitle() : $nextPageLabel;
            $label = sprintf('<span>%s</span>', $label);
            if ($nextPageIcon) {
                $label = $label . $nextPageIcon;
            }
            $replacements['{next}'] = $this->createLink($next->route, $label, $attribs);
        }

        return strtr($template, $replacements);
    }

    /**
     * @param string $route
     * @param int $status
     * @return void
     */
    public function functionRedirect($route, $status = 302)
    {
        $url = $this->urlGenerator->generateAbsolute($route);
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

        $branch = Application::getService('Menu\Page\Node')->findByRoute($route);
        $treeIterator = new Menu\Page\Iterator\TreeIterator($branch);
        $filterIterator = new Menu\Page\Iterator\FilterIterator($treeIterator);
        $filterIterator->setEnabled(!$showHidden);

        $htmlTree = new Menu\Page\Renderer\HtmlTree($filterIterator);
        $htmlTree->setMaxDepth($maxDepth);
        $htmlTree->setClass($class);
        $htmlTree->itemCallback = function ($node) {
            $menuItem = $node->getMenuItem();
            $href = $this->urlGenerator->generate($menuItem->route);
            return sprintf('<a href="%s">%s</a>', $href, $menuItem->getMenuTitle());
        };
        return $htmlTree->render();
    }

    /**
     * @param string $category
     * @param string $message
     * @param array $params
     */
    public function functionTranslate($category, $message, array $params = [])
    {
        return Application::getService('Translator')->translate($category, $message, $params);
    }

    /**
     * @param string $route
     * @return string
     */
    public function functionUrl($route)
    {
        return $this->urlGenerator->generate($route);
    }

    /**
     * @param string $type
     * @return \Traversable
     */
    public function functionMediafiles($type = 'images')
    {
        $finder = Finder\Finder::create()
            ->in($this->alias->get('@media'))
            ->hidden(true);

        if ($type == 'folders') {
            return $finder->directories();
        }

        $types = ['images', 'documents', 'archives', 'code', 'videos', 'audio'];
        $type = in_array($type, $types) ? $type : 'images';
        $extensions = $this->config->get('media.' . $type);
        return $finder->files()->extensions($extensions);
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
        return 0 === strpos($page->getPath(), '@post');
    }

    /**
     * @param string $alias
     * @return bool
     */
    public function testIsReadable($alias)
    {
        if (!is_string($alias) || empty($alias)) {
            return false;
        }
        $filename = $this->alias->get($alias);
        return is_readable($filename);
    }

    /**
     * @param string $alias
     * @return bool
     */
    public function testIsWritable($alias)
    {
        if (!is_string($alias) || empty($alias)) {
            return false;
        }
        $filename = $this->alias->get($alias);
        return is_writable($filename);
    }
}
