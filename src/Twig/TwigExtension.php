<?php
/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie\Twig;

use Ausi\SlugGenerator\SlugGeneratorInterface;
use Exception;
use Herbie\Alias;
use Herbie\Assets;
use Herbie\Config;
use Herbie\Environment;
use Herbie\Page;
use Herbie\Page\Iterator\FilterIterator;
use Herbie\Page\PageItem;
use Herbie\Page\PageList;
use Herbie\Page\PageTree;
use Herbie\Pagination;
use Herbie\Repository\DataRepositoryInterface;
use Herbie\Repository\PageRepositoryInterface;
use Herbie\Selector;
use Herbie\Translator;
use Herbie\Url\UrlGenerator;
use Traversable;
use Twig_Extension;
use Twig_Filter;
use Twig_Function;
use Twig_Test;

class TwigExtension extends Twig_Extension
{
    /** @var Alias */
    private $alias;

    /** @var Config */
    private $config;

    /** @var UrlGenerator */
    private $urlGenerator;

    /** @var Translator */
    private $translator;

    /** @var SlugGeneratorInterface */
    private $slugGenerator;

    /** @var Assets */
    private $assets;

    /** @var Environment */
    private $environment;

    /** @var DataRepositoryInterface */
    private $dataRepository;

    /** @var TwigRenderer */
    private $twigRenderer;

    /** @var PageRepositoryInterface */
    private $pageRepository;

    /**
     * TwigExtension constructor.
     * @param Alias $alias
     * @param Assets $assets
     * @param Config $config
     * @param DataRepositoryInterface $dataRepository
     * @param Environment $environment
     * @param PageRepositoryInterface $pageRepository
     * @param SlugGeneratorInterface $slugGenerator
     * @param Translator $translator
     * @param UrlGenerator $urlGenerator
     */
    public function __construct(
        Alias $alias,
        Assets $assets,
        Config $config,
        DataRepositoryInterface $dataRepository,
        Environment $environment,
        PageRepositoryInterface $pageRepository,
        SlugGeneratorInterface $slugGenerator,
        Translator $translator,
        UrlGenerator $urlGenerator
    ) {
        $this->alias = $alias;
        $this->config = $config;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->slugGenerator = $slugGenerator;
        $this->assets = $assets;
        $this->environment = $environment;
        $this->dataRepository = $dataRepository;
        $this->pageRepository = $pageRepository;
    }

    /**
     * @param TwigRenderer $twigRenderer
     */
    public function setTwigRenderer(TwigRenderer $twigRenderer): void
    {
        $this->twigRenderer = $twigRenderer;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new Twig_Filter('filesize', [$this, 'filterFilesize'], ['is_safe' => ['html']]),
            new Twig_Filter('filter', [$this, 'filterFilter'], ['is_variadic' => true]),
            new Twig_Filter('slugify', [$this, 'filterSlugify'], ['is_safe' => ['html']]),
            new Twig_Filter('strftime', [$this, 'filterStrftime']),
            new Twig_Filter('visible', [$this, 'filterVisible'], ['is_safe' => ['html']])
        ];
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        $options = ['is_safe' => ['html']];
        return [
            new Twig_Function('absurl', [$this, 'functionAbsUrl'], $options),
            new Twig_Function('addcss', [$this, 'functionAddCss'], $options),
            new Twig_Function('addjs', [$this, 'functionAddJs'], $options),
            new Twig_Function('asciitree', [$this, 'functionAsciiTree'], $options),
            new Twig_Function('bodyclass', [$this, 'functionBodyClass'], $options),
            new Twig_Function('breadcrumb', [$this, 'functionBreadcrumb'], $options),
            new Twig_Function('file', [$this, 'functionFile'], $options),
            new Twig_Function('image', [$this, 'functionImage'], $options),
            new Twig_Function('link', [$this, 'functionLink'], $options),
            new Twig_Function('listing', [$this, 'functionListing'], $options),
            new Twig_Function('menu', [$this, 'functionMenu'], $options),
            new Twig_Function('outputcss', [$this, 'functionOutputCss'], $options),
            new Twig_Function('outputjs', [$this, 'functionOutputJs'], $options),
            new Twig_Function('page_taxonomies', [$this, 'functionPageTaxonomies'], $options),
            new Twig_Function('pager', [$this, 'functionPager'], $options),
            new Twig_Function('pages_filtered', [$this, 'functionPagesFiltered'], $options),
            new Twig_Function('pages_recent', [$this, 'functionPagesRecent'], $options),
            new Twig_Function('pagetitle', [$this, 'functionPageTitle'], $options),
            new Twig_Function('sitemap', [$this, 'functionSitemap'], $options),
            new Twig_Function('snippet', [$this, 'functionSnippet'], ['is_variadic' => true]),
            new Twig_Function('taxonomy_archive', [$this, 'functionTaxonomyArchive'], $options),
            new Twig_Function('taxonomy_authors', [$this, 'functionTaxonomyAuthors'], $options),
            new Twig_Function('taxonomy_categories', [$this, 'functionTaxonomyCategories'], $options),
            new Twig_Function('taxonomy_tags', [$this, 'functionTaxonomyTags'], $options),
            new Twig_Function('translate', [$this, 'functionTranslate'], $options),
            new Twig_Function('url', [$this, 'functionUrl'], $options),
        ];
    }

    /**
     * @return array
     */
    public function getTests(): array
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
    private function buildHtmlAttributes(array $htmlOptions = []): string
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
    private function createLink(string $route, string $label, array $htmlAttributes = []): string
    {
        $url = $this->urlGenerator->generate($route);
        $attributesAsString = $this->buildHtmlAttributes($htmlAttributes);
        return sprintf('<a href="%s"%s>%s</a>', $url, $attributesAsString, $label);
    }

    /**
     * @param integer $size
     * @return string
     */
    public function filterFilesize(int $size): string
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
     * @param Traversable $iterator
     * @param array $selectors
     * @return array
     * @throws Exception
     * @todo Implement und document this twig filter
     */
    public function filterFilter(Traversable $iterator, array $selectors = []): array
    {
        $selector = new Selector(get_class($iterator));
        $items = $iterator->getItems();
        $filtered = $selector->find($selectors, $items);
        return $filtered;
    }

    /**
     * Creates a web friendly URL (slug) from a string.
     *
     * @param string $url
     * @return string
     */
    public function filterSlugify(string $url): string
    {
        return $this->slugGenerator->generate($url);
    }

    /**
     * @param string $date
     * @param string $format
     * @return string
     * @throws Exception
     */
    public function filterStrftime(string $date, string $format = '%x'): string
    {
        // timestamp?
        if (is_numeric($date)) {
            $date = date('Y-m-d H:i:s', $date);
        }
        $dateTime = new \DateTime($date);
        return strftime($format, $dateTime->getTimestamp());
    }

    /**
     * @param PageTree $tree
     * @return FilterIterator
     */
    public function filterVisible(PageTree $tree): FilterIterator
    {
        $treeIterator = new Page\Iterator\TreeIterator($tree);
        return new FilterIterator($treeIterator);
    }

    /**
     * @param string $route
     * @return string
     */
    public function functionAbsUrl(string $route): string
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
    public function functionAddCss(
        $paths,
        array $attr = [],
        string $group = null,
        bool $raw = false,
        int $pos = 1
    ): void {
        $this->assets->addCss($paths, $attr, $group, $raw, $pos);
    }

    /**
     * @param array|string $paths
     * @param array $attr
     * @param string $group
     * @param bool $raw
     * @param int $pos
     */
    public function functionAddJs(
        $paths,
        array $attr = [],
        ?string $group = null,
        bool $raw = false,
        int $pos = 1
    ): void {
        $this->assets->addJs($paths, $attr, $group, $raw, $pos);
    }

    /**
     * @param string $group
     * @return string
     */
    public function functionOutputCss(?string $group = null): string
    {
        return $this->assets->outputCss($group);
    }

    /**
     * @param string $group
     * @return string
     */
    public function functionOutputJs(?string $group = null): string
    {
        return $this->assets->outputJs($group);
    }

    /**
     * @param $routeParams
     * @param string $template
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function functionPagesFiltered(
        $routeParams,
        string $template = '@template/pages/filtered.twig'
    ): string {
        return $this->twigRenderer->renderTemplate($template, [
            'routeParams' => $routeParams
        ]);
    }

    /**
     * @param PageList|null $pageList
     * @param string $dateFormat
     * @param int $limit
     * @param string|null $pageType
     * @param bool $showDate
     * @param string $title
     * @param string $template
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function functionPagesRecent(
        PageList $pageList = null,
        string $dateFormat = '%e. %B %Y',
        int $limit = 5,
        string $pageType = null,
        bool $showDate = false,
        string $title = 'Recent posts',
        string $template = '@template/pages/recent.twig'
    ): string {
        if (is_null($pageList)) {
            $pageList = $this->pageRepository->findAll();
        }
        $recentPages = $pageList->getRecent($limit, $pageType);
        return $this->twigRenderer->renderTemplate($template, [
            'recentPages' => $recentPages,
            'dateFormat' => $dateFormat,
            'pageType' => $pageType,
            'showDate' => $showDate,
            'title' => $title
        ]);
    }

    /**
     * @param string $route
     * @param int $maxDepth
     * @param bool $showHidden
     * @param string $class
     * @return string
     */
    public function functionAsciiTree(
        string $route = '',
        int $maxDepth = -1,
        bool $showHidden = false,
        string $class = 'sitemap'
    ): string {

        // TODO use $class parameter
        $branch = $this->pageRepository->findAll()->getPageTree()->findByRoute($route);
        $treeIterator = new Page\Iterator\TreeIterator($branch);
        $filterIterator = new FilterIterator($treeIterator);
        $filterIterator->setEnabled(!$showHidden);

        $asciiTree = new Page\Renderer\AsciiTree($filterIterator);
        $asciiTree->setMaxDepth($maxDepth);
        return $asciiTree->render();
    }

    /**
     * @return string
     */
    public function functionBodyClass(): string
    {
        $route = trim($this->environment->getRoute(), '/');
        if (empty($route)) {
            $route = 'index';
        }
        // TODO retrieve page layout (available as request attribute HERBIE_PAGE)
        $layout = ''; //$this->page->getLayout();
        $class = sprintf('page-%s layout-%s', $route, $layout);
        return str_replace(['/', '.'], '-', $class);
    }

    /**
     * @param string $delim
     * @param array|string $homeLink
     * @param bool $reverse
     * @return string
     */
    public function functionBreadcrumb(
        string $delim = '',
        $homeLink = '',
        bool $reverse = false
    ): string {

        // TODO use string type for param $homeLink (like "route|label")

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

        foreach ($this->pageRepository->buildTrail() as $item) {
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
     * @param string $src
     * @param int $width
     * @param int $height
     * @param string $alt
     * @param string $class
     * @return string
     */
    public function functionImage(
        string $src,
        int $width = 0,
        int $height = 0,
        string $alt = '',
        string $class = ''
    ): string {
        $attribs = [];
        $attribs['src'] = $this->environment->getBasePath() . '/' . $src;
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
    public function functionLink(string $route, string $label, array $htmlAttributes = []): string
    {
        return $this->createLink($route, $label, $htmlAttributes);
    }

    /**
     * @param string $route
     * @param int $maxDepth
     * @param bool $showHidden
     * @param string $class
     * @return string
     */
    public function functionMenu(
        string $route = '',
        int $maxDepth = -1,
        bool $showHidden = false,
        string $class = 'menu'
    ): string {

        // TODO use $showHidden parameter
        $branch = $this->pageRepository->findAll()->getPageTree()->findByRoute($route);
        $treeIterator = new Page\Iterator\TreeIterator($branch);

        // using FilterCallback for better filtering of nested items
        $routeLine = $this->environment->getRouteLine();
        $callback = [new Page\Iterator\FilterCallback($routeLine), 'call'];
        $filterIterator = new \RecursiveCallbackFilterIterator($treeIterator, $callback);

        $htmlTree = new Page\Renderer\HtmlTree($filterIterator);
        $htmlTree->setMaxDepth($maxDepth);
        $htmlTree->setClass($class);
        $htmlTree->itemCallback = function (Page\PageTree $node) {
            $menuItem = $node->getMenuItem();
            $href = $this->urlGenerator->generate($menuItem->route);
            return sprintf('<a href="%s">%s</a>', $href, $menuItem->getMenuTitle());
        };
        return $htmlTree->render($this->environment->getRoute());
    }

    /**
     * @param string $delim
     * @param string $siteTitle
     * @param string $rootTitle
     * @param bool $reverse
     * @return string
     */
    public function functionPagetitle(
        string $delim = '/',
        string $siteTitle = '',
        string $rootTitle = '',
        bool $reverse = false
    ): string {

        $route = $this->environment->getRoute();
        $pageTrail = $this->pageRepository->findAll()->getPageTrail($route);
        $count = count($pageTrail);

        $titles = [];

        if (!empty($siteTitle)) {
            $titles[] = $siteTitle;
        }

        foreach ($pageTrail as $item) {
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

    public function functionPageTaxonomies(
        Page\Page $page,
        string $pageRoute = '',
        bool $renderAuthors = true,
        bool $renderCategories = true,
        bool $renderTags = true,
        string $template = '@template/page/taxonomies.twig'
    ): string {
        return $this->twigRenderer->renderTemplate($template, [
            'page' => $page,
            'pageRoute' => $pageRoute,
            'renderAuthors' => $renderAuthors,
            'renderCategories' => $renderCategories,
            'renderTags' => $renderTags
        ]);
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
        string $limit = '',
        string $template = '{prev}{next}',
        string $linkClass = '',
        string $nextPageLabel = '',
        string $prevPageLabel = '',
        string $prevPageIcon = '',
        string $nextPageIcon = ''
    ): string {
        $route = $this->environment->getRoute();
        $iterator = $this->pageRepository->findAll()->getIterator();

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

        $position = count($keys) - 2;
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
     * @param int $maxDepth
     * @param string $route
     * @param bool $showHidden
     * @param string $class
     * @return string
     */
    public function functionSitemap(
        $maxDepth = -1,
        $route = '',
        $showHidden = false,
        $class = 'sitemap'
    ): string {
        $branch = $this->pageRepository->findAll()->getPageTree()->findByRoute($route);
        $treeIterator = new Page\Iterator\TreeIterator($branch);
        $filterIterator = new FilterIterator($treeIterator);
        $filterIterator->setEnabled(!$showHidden);

        $htmlTree = new Page\Renderer\HtmlTree($filterIterator);
        $htmlTree->setMaxDepth($maxDepth);
        $htmlTree->setClass($class);
        $htmlTree->itemCallback = function (Page\PageTree $node) {
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
    public function functionTranslate(string $category, string $message, array $params = []): string
    {
        return $this->translator->translate($category, $message, $params);
    }

    /**
     * @param string $route
     * @return string
     */
    public function functionUrl(string $route): string
    {
        return $this->urlGenerator->generate($route);
    }

    /**
     * @param PageList $pageList
     * @param string $filter
     * @param string $sort
     * @param bool $shuffle
     * @param int $limit
     * @param string $template
     * @return string
     * @throws Exception
     */
    public function functionListing(
        PageList $pageList = null,
        string $filter = '',
        string $sort = '',
        bool $shuffle = false,
        int $limit = 10,
        string $template = '@snippet/listing.twig'
    ): string {

        if (is_null($pageList)) {
            $pageList = $this->pageRepository->findAll();
        }

        if (!empty($filter)) {
            list($field, $value) = explode('|', $filter);
            $pageList = $pageList->filter($field, $value);
        }

        if (!empty($sort)) {
            list($field, $direction) = explode('|', $sort);
            $pageList = $pageList->sort($field, $direction);
        }

        if (1 == (int)$shuffle) {
            $pageList = $pageList->shuffle();
        }

        // filter pages with empty title
        $pageList = $pageList->filter(function (PageItem $page) {
            return !empty($page->getTitle());
        });

        $pagination = new Pagination($pageList);
        $pagination->setLimit($limit);

        return $this->twigRenderer->renderTemplate($template, ['pagination' => $pagination]);
    }

    /**
     * @param string $path
     * @param array $options
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function functionSnippet(string $path, array $options = []): string
    {
        // TODO fix transformation of variadic camelCase to snake_case keys
        return $this->twigRenderer->renderTemplate($path, $options);
    }

    /**
     * @param PageList|null $pageList
     * @param string $pageRoute
     * @param string $pageType
     * @param bool $showCount
     * @param string $title
     * @param string $template
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function functionTaxonomyArchive(
        PageList $pageList = null,
        string $pageRoute = '',
        string $pageType = '',
        bool $showCount = false,
        string $title = 'Archive',
        string $template = '@template/taxonomy/archive.twig'
    ): string {
        if (is_null($pageList)) {
            $pageList = $this->pageRepository->findAll();
        }
        $months = $pageList->getMonths($pageType);
        return $this->twigRenderer->renderTemplate($template, [
            'months' => $months,
            'pageRoute' => $pageRoute,
            'pageType' => $pageType,
            'showCount' => $showCount,
            'title' => $title
        ]);
    }

    /**
     * @param PageList|null $pageList
     * @param string $pageRoute
     * @param string $pageType
     * @param bool $showCount
     * @param string $title
     * @param string $template
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function functionTaxonomyAuthors(
        PageList $pageList = null,
        string $pageRoute = '',
        string $pageType = '',
        bool $showCount = false,
        string $title = 'Authors',
        string $template = '@template/taxonomy/authors.twig'
    ): string {
        if (is_null($pageList)) {
            $pageList = $this->pageRepository->findAll();
        }
        $authors = $pageList->getAuthors($pageType);
        return $this->twigRenderer->renderTemplate($template, [
            'authors' => $authors,
            'pageRoute' => $pageRoute,
            'pageType' => $pageType,
            'showCount' => $showCount,
            'title' => $title
        ]);
    }

    /**
     * @param PageList|null $pageList
     * @param string $pageRoute
     * @param string $pageType
     * @param bool $showCount
     * @param string $title
     * @param string $template
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function functionTaxonomyCategories(
        PageList $pageList = null,
        string $pageRoute = '',
        string $pageType = '',
        bool $showCount = false,
        string $title = 'Categories',
        string $template = '@template/taxonomy/categories.twig'
    ): string {
        if (is_null($pageList)) {
            $pageList = $this->pageRepository->findAll();
        }
        $categories = $pageList->getCategories($pageType);
        return $this->twigRenderer->renderTemplate($template, [
            'categories' => $categories,
            'pageRoute' => $pageRoute,
            'pageType' => $pageType,
            'showCount' => $showCount,
            'title' => $title
        ]);
    }

    /**
     * @param PageList|null $pageList
     * @param string $pageRoute
     * @param string $pageType
     * @param bool $showCount
     * @param string $title
     * @param string $template
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function functionTaxonomyTags(
        PageList $pageList = null,
        string $pageRoute = '',
        string $pageType = '',
        bool $showCount = false,
        string $title = 'Tags',
        string $template = '@template/taxonomy/tags.twig'
    ): string {
        if (is_null($pageList)) {
            $pageList = $this->pageRepository->findAll();
        }
        $tags = $pageList->getTags($pageType);
        return $this->twigRenderer->renderTemplate($template, [
            'pageRoute' => $pageRoute,
            'pageType' => $pageType,
            'showCount' => $showCount,
            'tags' => $tags,
            'title' => $title
        ]);
    }

    /**
     * @param string $path
     * @param string $label
     * @param bool $info
     * @param array $attributes
     * @return string
     */
    public function functionFile(string $path, string $label = '', bool $info = false, array $attributes = []): string
    {
        $attributes['alt'] = $attributes['alt'] ?? '';

        if (!empty($info)) {
            $fileInfo = $this->getFileInfo($path);
        }

        $replace = [
            '{href}' => $path,
            '{attribs}' => $this->buildHtmlAttributes($attributes),
            '{label}' => empty($label) ? basename($path) : $label,
            '{info}' => empty($fileInfo) ? '' : sprintf('<span class="file-info">%s</span>', $fileInfo)
        ];
        return strtr('<a href="{href}" {attribs}>{label}</a>{info}', $replace);
    }

    /**
     * @param string $path
     * @return string
     */
    private function getFileInfo(string $path): string
    {
        if (!is_readable($path)) {
            return '';
        }
        $replace = [
            '{size}' => $this->filterFilesize(filesize($path)),
            '{extension}' => strtoupper(pathinfo($path, PATHINFO_EXTENSION))
        ];
        return strtr(' ({extension}, {size})', $replace);
    }

    /**
     * @param string $alias
     * @return bool
     */
    public function testIsReadable(string $alias): bool
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
    public function testIsWritable(string $alias): bool
    {
        if (!is_string($alias) || empty($alias)) {
            return false;
        }
        $filename = $this->alias->get($alias);
        return is_writable($filename);
    }
}
