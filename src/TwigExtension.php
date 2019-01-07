<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

use Ausi\SlugGenerator\SlugGeneratorInterface;
use Herbie\Menu;
use Herbie\Menu\MenuList;
use Herbie\Menu\MenuTree;
use Herbie\Menu\RootPath;
use Herbie\Repository\DataRepositoryInterface;
use Herbie\Url\UrlGenerator;
use Psr\Http\Message\ServerRequestInterface;
use Twig_Extension;
use Twig_Filter;
use Twig_Function;
use Twig_Test;

class TwigExtension extends Twig_Extension
{
    private $alias;
    private $config;
    private $request;
    private $urlGenerator;
    private $page;
    private $translator;
    private $slugGenerator;
    private $assets;
    private $menuList;
    private $menuTree;
    private $menuRootPath;
    private $environment;
    private $dataRepository;
    private $twigRenderer;

    /**
     * TwigExtension constructor.
     * @param Alias $alias
     * @param Config $config
     * @param ServerRequestInterface $request
     * @param UrlGenerator $urlGenerator
     * @param SlugGeneratorInterface $slugGenerator
     * @param Assets $assets
     * @param MenuList $menuList
     * @param MenuTree $menuTree
     * @param RootPath $menuRootPath
     * @param Environment $environment
     * @param DataRepositoryInterface $dataRepository
     * @param Translator $translator
     * @param TwigRenderer $twigRenderer
     */
    public function __construct(
        Alias $alias,
        Config $config,
        ServerRequestInterface $request,
        UrlGenerator $urlGenerator,
        SlugGeneratorInterface $slugGenerator,
        Assets $assets,
        MenuList $menuList,
        MenuTree $menuTree,
        RootPath $menuRootPath,
        Environment $environment,
        DataRepositoryInterface $dataRepository,
        Translator $translator,
        TwigRenderer $twigRenderer
    ) {
        $this->alias = $alias;
        $this->config = $config;
        $this->request = $request;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->slugGenerator = $slugGenerator;
        $this->assets = $assets;
        $this->menuList = $menuList;
        $this->menuTree = $menuTree;
        $this->menuRootPath = $menuRootPath;
        $this->environment = $environment;
        $this->twigRenderer = $twigRenderer;
        $this->dataRepository = $dataRepository;
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
    public function getFilters()
    {
        return [
            new Twig_Filter('filesize', [$this, 'filterFilesize'], ['is_safe' => ['html']]),
            new Twig_Filter('strftime', [$this, 'filterStrftime']),
            new Twig_Filter('urlify', [$this, 'filterUrlify'], ['is_safe' => ['html']]),
            new Twig_Filter('visible', [$this, 'filterVisible'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        $options = ['is_safe' => ['html']];
        return [
            new Twig_Function('absurl', [$this, 'functionAbsUrl'], $options),
            new Twig_Function('addcss', [$this, 'functionAddCss'], $options),
            new Twig_Function('addjs', [$this, 'functionAddJs'], $options),
            new Twig_Function('config', [$this, 'functionConfig'], $options),
            new Twig_Function('outputcss', [$this, 'functionOutputCss'], $options),
            new Twig_Function('outputjs', [$this, 'functionOutputJs'], $options),
            new Twig_Function('asciitree', [$this, 'functionAsciiTree'], $options),
            new Twig_Function('bodyclass', [$this, 'functionBodyClass'], $options),
            new Twig_Function('breadcrumb', [$this, 'functionBreadcrumb'], $options),
            new Twig_Function('image', [$this, 'functionImage'], $options),
            new Twig_Function('link', [$this, 'functionLink'], $options),
            new Twig_Function('menu', [$this, 'functionMenu'], $options),
            new Twig_Function('pagetitle', [$this, 'functionPageTitle'], $options),
            new Twig_Function('pager', [$this, 'functionPager'], $options),
            new Twig_Function('redirect', [$this, 'functionRedirect'], $options),
            new Twig_Function('sitemap', [$this, 'functionSitemap'], $options),
            new Twig_Function('translate', [$this, 'functionTranslate'], $options),
            new Twig_Function('url', [$this, 'functionUrl'], $options)
        ];
    }

    /**
     * @return array
     */
    public function getTests()
    {
        return [
            new Twig_Test('readable', [$this, 'testIsReadable']),
            new Twig_Test('writable', [$this, 'testIsWritable'])
        ];
    }

    /**
     * @param array $htmlOptions
     * @return string
     */
    private function buildHtmlAttributes($htmlOptions = [])
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
    private function createLink($route, $label, $htmlAttributes = [])
    {
        $url = $this->urlGenerator->generate($route);
        $attributesAsString = $this->buildHtmlAttributes($htmlAttributes);
        return sprintf('<a href="%s"%s>%s</a>', $url, $attributesAsString, $label);
    }

    /**
     * @param string $message
     * @return string
     */
    private function renderError($message)
    {
        $style = 'background:red;color:white;padding:4px;margin:2em 0';
        $message = 'Error: ' . $message;
        return sprintf('<div style="%s">%s</div>', $style, $message);
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
        return $this->slugGenerator->generate($url);
    }

    /**
     * @param Menu\MenuTree $tree
     * @return Menu\Iterator\FilterIterator
     */
    public function filterVisible($tree)
    {
        $treeIterator = new Menu\Iterator\TreeIterator($tree);
        return new Menu\Iterator\FilterIterator($treeIterator);
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
    public function functionAddCss($paths, array $attr = [], string $group = null, bool $raw = false, int $pos = 1)
    {
        $this->assets->addCss($paths, $attr, $group, $raw, $pos);
    }

    /**
     * @param array|string $paths
     * @param array $attr
     * @param string $group
     * @param bool $raw
     * @param int $pos
     */
    public function functionAddJs($paths, array $attr = [], string $group = null, bool $raw = false, int $pos = 1)
    {
        $this->assets->addJs($paths, $attr, $group, $raw, $pos);
    }

    /**
     * @param string $group
     * @return string
     */
    public function functionOutputCss($group = null)
    {
        return $this->assets->outputCss($group);
    }

    /**
     * @param string $group
     * @return string
     */
    public function functionOutputJs($group = null)
    {
        return $this->assets->outputJs($group);
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

        $branch = $this->menuTree->findByRoute($route);
        $treeIterator = new Menu\Iterator\TreeIterator($branch);
        $filterIterator = new Menu\Iterator\FilterIterator($treeIterator);
        $filterIterator->setEnabled(!$showHidden);

        $asciiTree = new Menu\Renderer\AsciiTree($filterIterator);
        $asciiTree->setMaxDepth($maxDepth);
        return $asciiTree->render();
    }

    /**
     * @return string
     */
    public function functionBodyClass()
    {
        $route = trim($this->environment->getRoute(), '/');
        if (empty($route)) {
            $route = 'index';
        }
        $layout = ''; //$this->page->layout;
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

        foreach ($this->getMenuPageRootPath() as $item) {
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

        $branch = $this->menuTree->findByRoute($route);
        $treeIterator = new Menu\Iterator\TreeIterator($branch);

        // using FilterCallback for better filtering of nested items
        $routeLine = $this->environment->getRouteLine();
        $callback = [new Menu\Iterator\FilterCallback($routeLine, $showHidden), 'call'];
        $filterIterator = new \RecursiveCallbackFilterIterator($treeIterator, $callback);

        $htmlTree = new Menu\Renderer\HtmlTree($filterIterator);
        $htmlTree->setMaxDepth($maxDepth);
        $htmlTree->setClass($class);
        $htmlTree->itemCallback = function (\Herbie\Node $node) {
            $menuItem = $node->getMenuItem();
            $href = $this->urlGenerator->generate($menuItem->route);
            return sprintf('<a href="%s">%s</a>', $href, $menuItem->getMenuTitle());
        };
        return $htmlTree->render($this->environment->getRoute());
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

        $count = count($this->menuRootPath);

        $titles = [];

        if (!empty($siteTitle)) {
            $titles[] = $siteTitle;
        }

        foreach ($this->menuRootPath as $item) {
            if ((1 == $count) && $item->isStartPage() && !empty($rootTitle)) {
                return $rootTitle;
            }
            $titles[] = $item->title;
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
    public function functionPager(
        $limit = '',
        $template = '{prev}{next}',
        $linkClass = '',
        $nextPageLabel = '',
        $prevPageLabel = '',
        $prevPageIcon = '',
        $nextPageIcon = ''
    ) {
        $route = $this->environment->getRoute();
        $iterator = $this->menuList->getIterator();

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

        $branch = $this->menuTree->findByRoute($route);
        $treeIterator = new Menu\Iterator\TreeIterator($branch);
        $filterIterator = new Menu\Iterator\FilterIterator($treeIterator);
        $filterIterator->setEnabled(!$showHidden);

        $htmlTree = new Menu\Renderer\HtmlTree($filterIterator);
        $htmlTree->setMaxDepth($maxDepth);
        $htmlTree->setClass($class);
        $htmlTree->itemCallback = function (Menu\MenuTree $node) {
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
     * @return string
     */
    public function functionTranslate($category, $message, array $params = [])
    {
        return $this->translator->translate($category, $message, $params);
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
