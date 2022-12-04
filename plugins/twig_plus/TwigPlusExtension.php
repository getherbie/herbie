<?php

declare(strict_types=1);

namespace herbie\sysplugin\twig_plus;

use herbie\Page;
use herbie\PageList;
use herbie\PageRepositoryInterface;
use herbie\PageTree;
use herbie\PageTreeFilterCallback;
use herbie\PageTreeFilterIterator;
use herbie\PageTreeHtmlRenderer;
use herbie\PageTreeIterator;
use herbie\PageTreeTextRenderer;
use herbie\Pagination;
use herbie\UrlManager;
use Twig\Environment;
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

    /**
     * TwigPlusExtension constructor.
     */
    public function __construct(
        Environment $environment,
        PageRepositoryInterface $pageRepository,
        UrlManager $urlManager
    ) {
        $this->environment = $environment;
        $this->pageRepository = $pageRepository;
        $this->urlManager = $urlManager;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        $options = ['is_safe' => ['html']];
        return [
            new TwigFunction('menu_ascii_tree', [$this, 'menuAsciiTree'], $options),
            new TwigFunction('menu_breadcrumb', [$this, 'menuBreadcrumb'], $options),
            new TwigFunction('menu_list', [$this, 'menuList'], $options),
            new TwigFunction('menu_pager', [$this, 'menuPager'], $options),
            new TwigFunction('menu_sitemap', [$this, 'menuSitemap'], $options),
            new TwigFunction('menu_tree', [$this, 'menuTree'], $options),
            new TwigFunction('page_taxonomies', [$this, 'pageTaxonomies'], $options),
            new TwigFunction('taxonomy_archive', [$this, 'taxonomyArchive'], $options),
            new TwigFunction('taxonomy_authors', [$this, 'taxonomyAuthors'], $options),
            new TwigFunction('taxonomy_categories', [$this, 'taxonomyCategories'], $options),
            new TwigFunction('taxonomy_tags', [$this, 'taxonomyTags'], $options)
        ];
    }

    public function menuAsciiTree(
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
     * @param array{0: string, 1?: string}|string $homeLink
     */
    public function menuBreadcrumb(
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

        [$route] = $this->urlManager->parseRequest();
        $pageTrail = $this->pageRepository->findAll()->getPageTrail($route);
        foreach ($pageTrail as $item) {
            $links[] = $this->createLink($item->getRoute(), $item->getMenuTitle());
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
    public function menuList(
        ?PageList $pageList = null,
        string $filter = '',
        string $sort = '',
        bool $shuffle = false,
        int $limit = 10,
        string $template = '@snippet/listing.twig'
    ): string {
        if ($pageList === null) {
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
        $pageList = $pageList->filter(function (Page $page) {
            return !empty($page->getTitle());
        });

        $pagination = new Pagination($pageList);
        $pagination->setLimit($limit);

        return $this->environment->render($template, ['pagination' => $pagination]);
    }

    public function menuTree(
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
            $href = $this->urlManager->createUrl($menuItem->getRoute());
            return sprintf('<a href="%s">%s</a>', $href, $menuItem->getMenuTitle());
        });

        [$currenRoute] = $this->urlManager->parseRequest();
        return $htmlTree->render($currenRoute);
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function pageTaxonomies(
        ?Page $page = null,
        string $pageRoute = '',
        bool $renderAuthors = true,
        bool $renderCategories = true,
        bool $renderTags = true,
        string $template = '@template/page/taxonomies.twig'
    ): string {
        return $this->environment->render($template, [
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
    public function menuPager(
        string $limit = '',
        string $prevPageLabel = '',
        string $nextPageLabel = '',
        string $prevPageIcon = '',
        string $nextPageIcon = '',
        string $cssClass = 'pager',
        string $template = '<div class="{class}">{prev}{next}</div>'
    ): string {
        [$route] = $this->urlManager->parseRequest();
        $pageList = $this->pageRepository->findAll();

        if ($limit !== '') {
            $pageList = $pageList->filter(function ($page) use ($limit) {
                return strpos($page->getRoute(), $limit) === 0;
            });
        }

        $prevPage = null;
        $currentPage = null;
        $nextPage = null;
        $lastPage = null;
        foreach ($pageList as $key => $page) {
            if ($currentPage) {
                $nextPage = $page;
                break;
            }
            if ($key === $route) {
                $prevPage = $lastPage;
                $currentPage = $page;
                continue;
            }
            $lastPage = $page;
        }

        $replacements = [
            '{class}' => $cssClass,
            '{prev}' => '',
            '{next}' => ''
        ];

        if (isset($prevPage)) {
            $label = empty($prevPageLabel) ? $prevPage->getMenuTitle() : $prevPageLabel;
            $label = sprintf('<span class="%s-label-prev">%s</span>', $cssClass, $label);
            if ($prevPageIcon) {
                $label = sprintf('<span class="%s-icon-prev">%s</span>%s', $cssClass, $prevPageIcon, $label);
            }
            $attribs = ['class' => $cssClass . '-link-prev'];
            $replacements['{prev}'] = $this->createLink($prevPage->getRoute(), $label, $attribs);
        }

        if (isset($nextPage)) {
            $label = empty($nextPageLabel) ? $nextPage->getMenuTitle() : $nextPageLabel;
            $label = sprintf('<span class="%s-label-next">%s</span>', $cssClass, $label);
            if ($nextPageIcon) {
                $label = sprintf('%s<span class="%s-icon-next">%s</span>', $label, $cssClass, $nextPageIcon);
            }
            $attribs = ['class' => $cssClass . '-link-next'];
            $replacements['{next}'] = $this->createLink($nextPage->getRoute(), $label, $attribs);
        }

        return strtr($template, $replacements);
    }

    public function menuSitemap(
        string $route = '',
        int $maxDepth = -1,
        bool $showHidden = false,
        string $class = 'sitemap'
    ): string {
        return $this->menuTree($route, $maxDepth, $showHidden, $class);
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function taxonomyArchive(
        ?PageList $pageList = null,
        string $pageRoute = '',
        string $pageType = '',
        bool $showCount = false,
        string $title = 'Archive',
        string $template = '@template/taxonomy/archive.twig'
    ): string {
        if ($pageList === null) {
            $pageList = $this->pageRepository->findAll();
        }
        $months = $pageList->getMonths($pageType);
        return $this->environment->render($template, [
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
    public function taxonomyAuthors(
        ?PageList $pageList = null,
        string $pageRoute = '',
        string $pageType = '',
        bool $showCount = false,
        string $title = 'Authors',
        string $template = '@template/taxonomy/authors.twig'
    ): string {
        if ($pageList === null) {
            $pageList = $this->pageRepository->findAll();
        }
        $authors = $pageList->getAuthors($pageType);
        return $this->environment->render($template, [
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
    public function taxonomyCategories(
        ?PageList $pageList = null,
        string $pageRoute = '',
        string $pageType = '',
        bool $showCount = false,
        string $title = 'Categories',
        string $template = '@template/taxonomy/categories.twig'
    ): string {
        if ($pageList === null) {
            $pageList = $this->pageRepository->findAll();
        }
        $categories = $pageList->getCategories($pageType);
        return $this->environment->render($template, [
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
    public function taxonomyTags(
        ?PageList $pageList = null,
        string $pageRoute = '',
        string $pageType = '',
        bool $showCount = false,
        string $title = 'Tags',
        string $template = '@template/taxonomy/tags.twig'
    ): string {
        if ($pageList === null) {
            $pageList = $this->pageRepository->findAll();
        }
        $tags = $pageList->getTags($pageType);
        return $this->environment->render($template, [
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
