<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;
use Twig_Extension;
use Twig_Function;

class TwigPlusExtension extends Twig_Extension
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var TwigRenderer
     */
    private $twigRenderer;

    /**
     * TwigPlusExtension constructor.
     * @param Environment $environment
     * @param PageRepositoryInterface $pageRepository
     * @param UrlGenerator $urlGenerator
     */
    public function __construct(
        Environment $environment,
        PageRepositoryInterface $pageRepository,
        UrlGenerator $urlGenerator
    ) {
        $this->environment = $environment;
        $this->pageRepository = $pageRepository;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        $options = ['is_safe' => ['html']];
        return [
            new Twig_Function('asciitree', [$this, 'functionAsciiTree'], $options),
            new Twig_Function('bodyclass', [$this, 'functionBodyClass'], ['needs_context' => true]),
            new Twig_Function('breadcrumb', [$this, 'functionBreadcrumb'], $options),
            new Twig_Function('listing', [$this, 'functionListing'], $options),
            new Twig_Function('menu', [$this, 'functionMenu'], $options),
            new Twig_Function('page_taxonomies', [$this, 'functionPageTaxonomies'], $options),
            new Twig_Function('pager', [$this, 'functionPager'], $options),
            new Twig_Function('pages_filtered', [$this, 'functionPagesFiltered'], $options),
            new Twig_Function('pages_recent', [$this, 'functionPagesRecent'], $options),
            new Twig_Function('pagetitle', [$this, 'functionPageTitle']),
            new Twig_Function('sitemap', [$this, 'functionSitemap'], $options),
            new Twig_Function('snippet', [$this, 'functionSnippet'], ['is_variadic' => true]),
            new Twig_Function('taxonomy_archive', [$this, 'functionTaxonomyArchive'], $options),
            new Twig_Function('taxonomy_authors', [$this, 'functionTaxonomyAuthors'], $options),
            new Twig_Function('taxonomy_categories', [$this, 'functionTaxonomyCategories'], $options),
            new Twig_Function('taxonomy_tags', [$this, 'functionTaxonomyTags'], $options)
        ];
    }

    /**
     * @param TwigRenderer $twigRenderer
     */
    public function setTwigRenderer(TwigRenderer $twigRenderer): void
    {
        $this->twigRenderer = $twigRenderer;
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
        $treeIterator = new PageTreeIterator($branch);
        $filterIterator = new PageTreeFilterIterator($treeIterator);
        $filterIterator->setEnabled(!$showHidden);

        $asciiTree = new PageTreeTextRenderer($filterIterator);
        $asciiTree->setMaxDepth($maxDepth);
        return $asciiTree->render();
    }

    /**
     * @param array $context
     * @return string
     */
    public function functionBodyClass(array $context): string
    {
        $page = 'error';
        if (isset($context['page'])) {
            $route = $context['page']->getRoute();
            $page = !empty($route) ? $route : 'index';
        }

        $layout = 'default';
        if (isset($context['page'])) {
            $layout = $context['page']->getLayout();
        }

        $theme = 'default';
        if (!empty($context['theme'])) {
            $theme = $context['theme'];
        }

        $language = 'en';
        if (isset($context['site'])) {
            $language = $context['site']->getLanguage();
        }

        $class = sprintf('page-%s theme-%s layout-%s language-%s', $page, $theme, $layout, $language);
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
     * @param PageList $pageList
     * @param string $filter
     * @param string $sort
     * @param bool $shuffle
     * @param int $limit
     * @param string $template
     * @return string
     * @throws \Exception
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
        $treeIterator = new PageTreeIterator($branch);

        // using FilterCallback for better filtering of nested items
        $routeLine = $this->environment->getRouteLine();
        $filterCallback = new PageTreeFilterCallback($routeLine);
        $filterIterator = new \RecursiveCallbackFilterIterator($treeIterator, $filterCallback);

        $htmlTree = new PageTreeHtmlRenderer($filterIterator);
        $htmlTree->setMaxDepth($maxDepth);
        $htmlTree->setClass($class);
        $htmlTree->itemCallback = function (PageTree $node) {
            $menuItem = $node->getMenuItem();
            $href = $this->urlGenerator->generate($menuItem->route);
            return sprintf('<a href="%s">%s</a>', $href, $menuItem->getMenuTitle());
        };
        return $htmlTree->render($this->environment->getRoute());
    }

    public function functionPageTaxonomies(
        Page $page,
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
     * @param $routeParams
     * @param string $template
     * @return string
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
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
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
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
        $treeIterator = new PageTreeIterator($branch);
        $filterIterator = new PageTreeFilterIterator($treeIterator);
        $filterIterator->setEnabled(!$showHidden);

        $htmlTree = new PageTreeHtmlRenderer($filterIterator);
        $htmlTree->setMaxDepth($maxDepth);
        $htmlTree->setClass($class);
        $htmlTree->itemCallback = function (PageTree $node) {
            $menuItem = $node->getMenuItem();
            $href = $this->urlGenerator->generate($menuItem->route);
            return sprintf('<a href="%s">%s</a>', $href, $menuItem->getMenuTitle());
        };
        return $htmlTree->render();
    }

    /**
     * @param string $path
     * @param array $options
     * @return string
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
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
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
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
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
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
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
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
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
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
}
