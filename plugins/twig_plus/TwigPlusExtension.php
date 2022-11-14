<?php

declare(strict_types=1);

namespace herbie\sysplugin\twig_plus;

use herbie\Environment;
use herbie\Page;
use herbie\PageItem;
use herbie\PageList;
use herbie\PageRepositoryInterface;
use herbie\PageTree;
use herbie\PageTreeFilterCallback;
use herbie\PageTreeFilterIterator;
use herbie\PageTreeHtmlRenderer;
use herbie\PageTreeIterator;
use herbie\PageTreeTextRenderer;
use herbie\Pagination;
use herbie\TwigRenderer;
use herbie\UrlManager;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TwigPlusExtension extends AbstractExtension
{
    private Environment $environment;

    private PageRepositoryInterface $pageRepository;

    private UrlManager $urlManager;

    private TwigRenderer $twigRenderer;

    /**
     * TwigPlusExtension constructor.
     */
    public function __construct(
        Environment $environment,
        PageRepositoryInterface $pageRepository,
        TwigRenderer $twigRenderer,
        UrlManager $urlManager
    ) {
        $this->environment = $environment;
        $this->pageRepository = $pageRepository;
        $this->twigRenderer = $twigRenderer;
        $this->urlManager = $urlManager;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        $options = ['is_safe' => ['html']];
        return [
            new TwigFunction('ascii_tree', [$this, 'functionAsciiTree'], $options),
            new TwigFunction('body_class', [$this, 'functionBodyClass'], ['needs_context' => true]),
            new TwigFunction('breadcrumb', [$this, 'functionBreadcrumb'], $options),
            new TwigFunction('listing', [$this, 'functionListing'], $options),
            new TwigFunction('menu', [$this, 'functionMenu'], $options),
            new TwigFunction('page_taxonomies', [$this, 'functionPageTaxonomies'], $options),
            new TwigFunction('pager', [$this, 'functionPager'], $options),
            new TwigFunction('pages_filtered', [$this, 'functionPagesFiltered'], $options),
            new TwigFunction('pages_recent', [$this, 'functionPagesRecent'], $options),
            new TwigFunction('page_title', [$this, 'functionPageTitle']),
            new TwigFunction('sitemap', [$this, 'functionSitemap'], $options),
            new TwigFunction('snippet', [$this, 'functionSnippet'], ['is_safe' => ['all']]),
            new TwigFunction('taxonomy_archive', [$this, 'functionTaxonomyArchive'], $options),
            new TwigFunction('taxonomy_authors', [$this, 'functionTaxonomyAuthors'], $options),
            new TwigFunction('taxonomy_categories', [$this, 'functionTaxonomyCategories'], $options),
            new TwigFunction('taxonomy_tags', [$this, 'functionTaxonomyTags'], $options)
        ];
    }

    public function functionAsciiTree(
        string $route = '',
        int $maxDepth = -1,
        bool $showHidden = false
    ): string {
        // TODO use $class parameter
        $branch = $this->pageRepository->findAll()->getPageTree()->findByRoute($route);
        if ($branch === null) {
            return '';
        }

        $treeIterator = new PageTreeIterator($branch);
        $filterIterator = new PageTreeFilterIterator($treeIterator, !$showHidden);

        $asciiTree = new PageTreeTextRenderer($filterIterator);
        $asciiTree->setMaxDepth($maxDepth);
        return $asciiTree->render();
    }

    /**
     * @param array<string, mixed> $context
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
     * @param array{0: string, 1?: string}|string $homeLink
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

        $route = $this->environment->getRoute();
        $pageTrail = $this->pageRepository->findAll()->getPageTrail($route);
        foreach ($pageTrail as $item) {
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
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function functionListing(
        ?PageList $pageList = null,
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
            [$field, $value] = explode('|', $filter);
            $pageList = $pageList->filter($field, $value);
        }

        if (!empty($sort)) {
            [$field, $direction] = explode('|', $sort);
            $pageList = $pageList->sort($field, $direction);
        }

        if ($shuffle) {
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

    public function functionMenu(
        string $route = '',
        int $maxDepth = -1,
        bool $showHidden = false,
        string $class = 'menu'
    ): string {
        // NOTE duplicated code, see function sitemap
        $branch = $this->pageRepository->findAll()->getPageTree()->findByRoute($route);
        if ($branch === null) {
            return '';
        }

        $treeIterator = new PageTreeIterator($branch);
        $filterIterator = new PageTreeFilterIterator($treeIterator, !$showHidden);

        $htmlTree = new PageTreeHtmlRenderer($filterIterator);
        $htmlTree->setMaxDepth($maxDepth);
        $htmlTree->setClass($class);
        $htmlTree->setItemCallback(function (PageTree $node) {
            $menuItem = $node->getMenuItem();
            $href = $this->urlManager->createUrl($menuItem->route);
            return sprintf('<a href="%s">%s</a>', $href, $menuItem->getMenuTitle());
        });
        return $htmlTree->render();
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function functionPageTaxonomies(
        ?Page $page = null,
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
     * @throws \Exception
     */
    public function functionPager(
        string $limit = '',
        string $prevPageLabel = '',
        string $nextPageLabel = '',
        string $prevPageIcon = '',
        string $nextPageIcon = '',
        string $cssClass = 'pager',
        string $template = '<div class="{class}">{prev}{next}</div>'
    ): string {
        $route = $this->environment->getRoute();
        $pageList = $this->pageRepository->findAll();

        if (strlen($limit) > 0) {
            $pageList = $pageList->filter(function ($pageItem) use ($limit) {
                return strpos($pageItem->route, $limit) === 0;
            });
        }

        $prevPageItem = null;
        $currentPageItem = null;
        $nextPageItem = null;
        $lastPageItem = null;
        foreach ($pageList as $key => $pageItem) {
            if ($currentPageItem) {
                $nextPageItem = $pageItem;
                break;
            }
            if ($key === $route) {
                $prevPageItem = $lastPageItem;
                $currentPageItem = $pageItem;
                continue;
            }
            $lastPageItem = $pageItem;
        }

        $replacements = [
            '{class}' => $cssClass,
            '{prev}' => '',
            '{next}' => ''
        ];

        if (isset($prevPageItem)) {
            $label = empty($prevPageLabel) ? $prevPageItem->getMenuTitle() : $prevPageLabel;
            $label = sprintf('<span class="%s-label-prev">%s</span>', $cssClass, $label);
            if ($prevPageIcon) {
                $label = sprintf('<span class="%s-icon-prev">%s</span>%s', $cssClass, $prevPageIcon, $label);
            }
            $attribs = ['class' => $cssClass . '-link-prev'];
            $replacements['{prev}'] = $this->createLink($prevPageItem->route, $label, $attribs);
        }

        if (isset($nextPageItem)) {
            $label = empty($nextPageLabel) ? $nextPageItem->getMenuTitle() : $nextPageLabel;
            $label = sprintf('<span class="%s-label-next">%s</span>', $cssClass, $label);
            if ($nextPageIcon) {
                $label = sprintf('%s<span class="%s-icon-next">%s</span>', $label, $cssClass, $nextPageIcon);
            }
            $attribs = ['class' => $cssClass . '-link-next'];
            $replacements['{next}'] = $this->createLink($nextPageItem->route, $label, $attribs);
        }

        return strtr($template, $replacements);
    }

    /**
     * @param array<string, string> $routeParams
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function functionPagesFiltered(
        array $routeParams,
        string $template = '@template/pages/filtered.twig'
    ): string {
        return $this->twigRenderer->renderTemplate($template, [
            'routeParams' => $routeParams
        ]);
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function functionPagesRecent(
        ?PageList $pageList = null,
        string $dateFormat = '%e. %B %Y',
        int $limit = 5,
        ?string $pageType = null,
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

    public function functionPagetitle(
        string $delim = ' / ',
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
            if ((1 === $count) && $item->isStartPage() && !empty($rootTitle)) {
                return $rootTitle;
            }
            $titles[] = $item->title;
        }

        if (!empty($reverse)) {
            $titles = array_reverse($titles);
        }

        return implode($delim, $titles);
    }

    public function functionSitemap(
        string $route = '',
        int $maxDepth = -1,
        bool $showHidden = false,
        string $class = 'sitemap'
    ): string {
        return $this->functionMenu($route, $maxDepth, $showHidden, $class);
    }

    /**
     * @param array<string, mixed> $context
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function functionSnippet(string $path, array $context = []): string
    {
        return $this->twigRenderer->renderTemplate($path, $context);
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function functionTaxonomyArchive(
        ?PageList $pageList = null,
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
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function functionTaxonomyAuthors(
        ?PageList $pageList = null,
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
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function functionTaxonomyCategories(
        ?PageList $pageList = null,
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
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function functionTaxonomyTags(
        ?PageList $pageList = null,
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
     * @param array<string, string> $htmlOptions
     */
    protected function buildHtmlAttributes(array $htmlOptions = []): string
    {
        $attributes = '';
        foreach ($htmlOptions as $key => $value) {
            $attributes .= $key . '="' . $value . '" ';
        }
        return trim($attributes);
    }

    /**
     * @param array<string, string> $htmlAttributes
     */
    protected function createLink(string $route, string $label, array $htmlAttributes = []): string
    {
        $url = $this->urlManager->createUrl($route);
        $attributesAsString = $this->buildHtmlAttributes($htmlAttributes);
        return sprintf('<a href="%s"%s>%s</a>', $url, $attributesAsString, $label);
    }
}
