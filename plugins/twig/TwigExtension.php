<?php

declare(strict_types=1);

namespace herbie\sysplugins\twig;

use Ausi\SlugGenerator\SlugGenerator;
use Exception;
use herbie\Alias;
use herbie\Assets;
use herbie\Config;
use herbie\Page;
use herbie\PageList;
use herbie\PageRepositoryInterface;
use herbie\PageTree;
use herbie\PageTreeFilterIterator;
use herbie\PageTreeHtmlRenderer;
use herbie\PageTreeIterator;
use herbie\PageTreeTextRenderer;
use herbie\Pagination;
use herbie\QueryBuilder;
use herbie\Site;
use herbie\Translator;
use herbie\UrlManager;
use Traversable;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

use function herbie\file_size;
use function herbie\str_trailing_slash;

final class TwigExtension extends AbstractExtension
{
    private Alias $alias;
    private Assets $assets;
    private Config $config;
    private Environment $environment;
    private PageRepositoryInterface $pageRepository;
    private SlugGenerator $slugGenerator;
    private Translator $translator;
    private UrlManager $urlManager;

    /**
     * TwigExtension constructor.
     */
    public function __construct(
        Alias $alias,
        Assets $assets,
        Config $config,
        Environment $environment,
        PageRepositoryInterface $pageRepository,
        SlugGenerator $slugGenerator,
        Translator $translator,
        UrlManager $urlManager
    ) {
        $this->alias = $alias;
        $this->assets = $assets;
        $this->config = $config;
        $this->environment = $environment;
        $this->pageRepository = $pageRepository;
        $this->slugGenerator = $slugGenerator;
        $this->translator = $translator;
        $this->urlManager = $urlManager;
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_size', [$this, 'filterFilesize']),
            new TwigFilter('slugify', [$this, 'filterSlugify']),
            new TwigFilter('visible', [$this, 'filterVisible'], ['deprecated' => true]) // doesn't work properly
        ];
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('css_add', [$this, 'cssAdd']),
            new TwigFunction('css_classes', [$this, 'cssClasses'], ['needs_context' => true]),
            new TwigFunction('css_out', [$this, 'cssOut'], ['is_safe' => ['html']]),
            new TwigFunction('image', [$this, 'image'], ['is_safe' => ['html']]),
            new TwigFunction('js_add', [$this, 'jsAdd']),
            new TwigFunction('js_out', [$this, 'jsOut'], ['is_safe' => ['html']]),
            new TwigFunction('link_file', [$this, 'linkFile'], ['is_safe' => ['html']]),
            new TwigFunction('link_mail', [$this, 'linkMail'], ['is_safe' => ['html']]),
            new TwigFunction('link_media', [$this, 'linkMedia'], ['is_safe' => ['html']]),
            new TwigFunction('link_page', [$this, 'linkPage'], ['is_safe' => ['html']]),
            new TwigFunction('menu_ascii_tree', [$this, 'menuAsciiTree'], ['is_safe' => ['html']]),
            new TwigFunction('menu_breadcrumb', [$this, 'menuBreadcrumb'], ['is_safe' => ['html']]),
            new TwigFunction('menu_list', [$this, 'menuList'], ['is_safe' => ['html']]),
            new TwigFunction('menu_pager', [$this, 'menuPager'], ['is_safe' => ['html']]),
            new TwigFunction('menu_sitemap', [$this, 'menuSitemap'], ['is_safe' => ['html']]),
            new TwigFunction('menu_tree', [$this, 'menuTree'], ['is_safe' => ['html']]),
            new TwigFunction('page_title', [$this, 'pageTitle'], ['needs_context' => true]),
            new TwigFunction('query', [$this, 'query']),
            new TwigFunction('snippet', [$this, 'snippet'], ['is_safe' => ['all']]),
            new TwigFunction('translate', [$this, 'translate']),
            new TwigFunction('url_rel', [$this, 'urlRelative']),
            new TwigFunction('url_abs', [$this, 'urlAbsolute']),
        ];
    }

    /**
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest('file_readable', [$this, 'testIsReadable']),
            new TwigTest('file_writable', [$this, 'testIsWritable'])
        ];
    }

    /**
     * Creates a web friendly URL (slug) from a string.
     */
    public function filterSlugify(string $url): string
    {
        return $this->slugGenerator->generate($url);
    }

    public function filterVisible(PageTree $tree): PageTreeFilterIterator
    {
        $treeIterator = new PageTreeIterator($tree);
        return new PageTreeFilterIterator($treeIterator);
    }

    public function cssAdd(
        array|string $paths,
        array $attr = [],
        ?string $group = null,
        bool $raw = false,
        int $pos = 1
    ): void {
        $this->assets->addCss($paths, $attr, $group, $raw, $pos);
    }

    public function jsAdd(
        array|string $paths,
        array $attr = [],
        ?string $group = null,
        bool $raw = false,
        int $pos = 1
    ): void {
        $this->assets->addJs($paths, $attr, $group, $raw, $pos);
    }

    /**
     * @param array<string, mixed> $context
     * @return string
     */
    public function cssClasses(array $context): string
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

    public function linkMedia(
        string $path,
        string $label = '',
        bool $info = false,
        array $attribs = []
    ): string {
        $attribs['alt'] = $attribs['alt'] ?? '';
        $attribs['class'] = $attribs['class'] ?? 'link__label';

        $baseUrl = str_trailing_slash($this->config->getAsString('components.downloadMiddleware.route'));
        $storagePath = str_trailing_slash($this->config->getAsString('components.downloadMiddleware.storagePath'));

        // combine url and path
        $href = $this->urlManager->createUrl($baseUrl . $path);
        $path = $this->alias->get($storagePath . $path);

        if (!empty($info)) {
            $fileInfo = $this->getFileInfo($path);
        }

        $replace = [
            '{href}' => $href,
            '{attribs}' => $this->buildHtmlAttributes($attribs),
            '{label}' => empty($label) ? basename($path) : $label,
            '{info}' => empty($fileInfo) ? '' : sprintf('<span class="link__info">%s</span>', $fileInfo)
        ];
        return strtr('<span class="link link--media"><a href="{href}" {attribs}>{label}</a>{info}</span>', $replace);
    }

    private function getFileInfo(string $path): string
    {
        if (!is_readable($path)) {
            return '';
        }
        $replace = [
            '{size}' => $this->filterFilesize(file_size($path)),
            '{extension}' => strtoupper(pathinfo($path, PATHINFO_EXTENSION))
        ];
        return strtr(' ({extension}, {size})', $replace);
    }

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
        return str_replace(',', '.', (string)round($size, 1)) . ' ' . $units[$i];
    }

    /**
     * @param array<string, string> $attribs
     */
    private function buildHtmlAttributes(array $attribs = []): string
    {
        $attribsAsString = '';
        foreach ($attribs as $key => $value) {
            $attribsAsString .= $key . '="' . $value . '" ';
        }
        return trim($attribsAsString);
    }

    public function linkMail(
        string $email,
        ?string $label = null,
        array $attribs = [],
        string $template = '@snippet/link_mail.twig'
    ): string {
        $attribs['href'] = 'mailto:' . $email;
        $attribs['class'] = $attribs['class'] ?? 'link__label';

        ksort($attribs);

        $context = [
            'attribs' => $attribs,
            'label' => $label ?? $email,
        ];

        try {
            return $this->environment->render($template, $context);
        } catch (Error $e) {
            return $email;
        }
    }

    public function menuAsciiTree(
        string $route = '',
        int $depth = -1,
        bool $hidden = false
    ): string {
        // TODO use $class parameter
        $branch = $this->pageRepository->findAll()->getPageTree()->findByRoute($route);
        if ($branch === null) {
            return '';
        }

        $treeIterator = new PageTreeIterator($branch);
        $filterIterator = new PageTreeFilterIterator($treeIterator, !$hidden);

        $asciiTree = new PageTreeTextRenderer($filterIterator);
        $asciiTree->setMaxDepth($depth);
        return $asciiTree->render();
    }

    /**
     * @param array{0: string, 1?: string}|string $home
     */
    public function menuBreadcrumb(
        string $delim = '',
        array|string $home = '',
        bool $reverse = false
    ): string {
        // TODO use string type for param $homeLink (like "route|label")

        $links = [];

        if (!empty($home)) {
            if (is_array($home)) {
                $route = reset($home);
                $label = isset($home[1]) ? $home[1] : 'Home';
            } else {
                $route = $home;
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
     * @param array<string, string> $attribs
     */
    protected function createLink(string $route, string $label, array $attribs = []): string
    {
        $url = $this->urlManager->createUrl($route);
        $attribsAsString = $this->buildHtmlAttributes($attribs);
        return sprintf('<a href="%s"%s>%s</a>', $url, $attribsAsString, $label);
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function menuList(
        ?PageList $page_list = null,
        string $filter = '',
        string $sort = '',
        bool $shuffle = false,
        int $limit = 10,
        string $template = '@snippet/listing.twig'
    ): string {
        if ($page_list === null) {
            $page_list = $this->pageRepository->findAll();
        }

        if (!empty($filter)) {
            [$field, $value] = explode('|', $filter);
            $page_list = $page_list->filter($field, $value);
        }

        if (!empty($sort)) {
            [$field, $direction] = explode('|', $sort);
            $page_list = $page_list->sort($field, $direction);
        }

        if ($shuffle) {
            $page_list = $page_list->shuffle();
        }

        // filter pages with empty title
        $page_list = $page_list->filter(function (Page $page) {
            return !empty($page->getTitle());
        });

        $pagination = new Pagination($page_list);
        $pagination->setLimit($limit);

        return $this->environment->render($template, ['pagination' => $pagination]);
    }

    /**
     * @throws Exception
     */
    public function menuPager(
        string $limit = '',
        string $prev_label = '',
        string $next_label = '',
        string $prev_icon = '',
        string $next_icon = '',
        string $class = 'pager',
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
            '{class}' => $class,
            '{prev}' => '',
            '{next}' => ''
        ];

        if (isset($prevPage)) {
            $label = empty($prev_label) ? $prevPage->getMenuTitle() : $prev_label;
            $label = sprintf('<span class="%s-label-prev">%s</span>', $class, $label);
            if ($prev_icon) {
                $label = sprintf('<span class="%s-icon-prev">%s</span>%s', $class, $prev_icon, $label);
            }
            $attribs = ['class' => $class . '-link-prev'];
            $replacements['{prev}'] = $this->createLink($prevPage->getRoute(), $label, $attribs);
        }

        if (isset($nextPage)) {
            $label = empty($next_label) ? $nextPage->getMenuTitle() : $next_label;
            $label = sprintf('<span class="%s-label-next">%s</span>', $class, $label);
            if ($next_icon) {
                $label = sprintf('%s<span class="%s-icon-next">%s</span>', $label, $class, $next_icon);
            }
            $attribs = ['class' => $class . '-link-next'];
            $replacements['{next}'] = $this->createLink($nextPage->getRoute(), $label, $attribs);
        }

        return strtr($template, $replacements);
    }

    public function menuSitemap(
        string $route = '',
        int $depth = -1,
        bool $hidden = false,
        string $class = 'sitemap'
    ): string {
        return $this->menuTree($route, $depth, $hidden, $class);
    }

    public function menuTree(
        string $route = '',
        int $depth = -1,
        bool $hidden = false,
        string $class = 'menu'
    ): string {
        // NOTE duplicated code, see function sitemap
        $branch = $this->pageRepository->findAll()->getPageTree()->findByRoute($route);
        if ($branch === null) {
            return '';
        }

        $treeIterator = new PageTreeIterator($branch);
        $filterIterator = new PageTreeFilterIterator($treeIterator, !$hidden);

        $htmlTree = new PageTreeHtmlRenderer($filterIterator);
        $htmlTree->setMaxDepth($depth);
        $htmlTree->setClass($class);
        $htmlTree->setItemCallback(function (PageTree $node) {
            $menuItem = $node->getMenuItem();
            $href = $this->urlManager->createUrl($menuItem->getRoute());
            return sprintf('<a href="%s">%s</a>', $href, $menuItem->getMenuTitle());
        });

        [$currenRoute] = $this->urlManager->parseRequest();
        return $htmlTree->render($currenRoute);
    }

    public function cssOut(?string $group = null, bool $timestamp = false): string
    {
        return $this->assets->outputCss($group, $timestamp);
    }

    public function jsOut(?string $group = null, bool $timestamp = false): string
    {
        return $this->assets->outputJs($group, $timestamp);
    }

    /**
     * @param array<string, mixed> $context
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function snippet(string $path, array $context = []): string
    {
        return $this->environment->render($path, $context);
    }

    public function image(
        string $src,
        int $width = 0,
        int $height = 0,
        string $alt = '',
        string $class = ''
    ): string {
        $attribs = [];
        $attribs['src'] = $this->urlManager->createUrl('/') . $src;
        $attribs['alt'] = $alt;
        if (!empty($width)) {
            $attribs['width'] = (string)$width;
        }
        if (!empty($height)) {
            $attribs['height'] = (string)$height;
        }
        if (!empty($class)) {
            $attribs['class'] = $class;
        }
        return sprintf('<img %s>', $this->buildHtmlAttributes($attribs));
    }

    public function linkPage(string $route, string $label, array $attribs = []): string
    {
        $scheme = parse_url($route, PHP_URL_SCHEME);
        if ($scheme === null) {
            $class = 'link--page';
            $href = $this->urlManager->createUrl($route);
        } else {
            $class = 'link--external';
            $href = $route;
        }

        $attribs['class'] = $attribs['class'] ?? '';
        $attribs['class'] = trim($attribs['class'] . ' link__label');

        $replace = [
            '{class}' => $class,
            '{href}' => $href,
            '{attribs}' => $this->buildHtmlAttributes($attribs),
            '{label}' => $label,
        ];

        $template = '<span class="link {class}"><a href="{href}" {attribs}>{label}</a></span>';
        return strtr($template, $replace);
    }

    /**
     * @param array{site: Site} $context
     */
    public function pageTitle(
        array $context,
        string $delim = ' / ',
        string $site_title = '',
        string $root_title = '',
        bool $reverse = false
    ): string {
        $pageTrail = $context['site']->getPageTrail();
        $count = count($pageTrail);

        $titles = [];

        if (!empty($site_title)) {
            $titles[] = $site_title;
        }

        foreach ($pageTrail as $item) {
            if ((1 === $count) && $item->isStartPage() && !empty($root_title)) {
                return $root_title;
            }
            $titles[] = $item->getTitle();
        }

        if (!empty($reverse)) {
            $titles = array_reverse($titles);
        }

        return implode($delim, $titles);
    }

    public function query(iterable $iterator): QueryBuilder
    {
        if ($iterator instanceof Traversable) {
            $data = iterator_to_array($iterator);
        } else {
            $data = (array)$iterator;
        }
        return (new QueryBuilder())->from($data);
    }

    public function translate(string $category = '', string $message = '', array $params = []): string
    {
        return $this->translator->translate($category, $message, $params);
    }

    public function urlRelative(string $route = ''): string
    {
        return $this->urlManager->createUrl($route);
    }

    public function urlAbsolute(string $route = ''): string
    {
        return $this->urlManager->createAbsoluteUrl($route);
    }

    public function linkFile(string $path, string $label = '', bool $info = false, array $attribs = []): string
    {
        $attribs['class'] = $attribs['class'] ?? 'link__label';

        if (!empty($info)) {
            $fileInfo = $this->getFileInfo($path);
        }

        $replace = [
            '{href}' => $path,
            '{attribs}' => $this->buildHtmlAttributes($attribs),
            '{label}' => empty($label) ? basename($path) : $label,
            '{info}' => empty($fileInfo) ? '' : sprintf('<span class="link__info">%s</span>', $fileInfo)
        ];
        return strtr('<span class="link link--file"><a href="{href}" {attribs}>{label}</a>{info}</span>', $replace);
    }

    public function testIsReadable(string $alias): bool
    {
        if ($alias === '') {
            return false;
        }
        $filename = $this->alias->get($alias);
        return is_readable($filename);
    }

    public function testIsWritable(string $alias): bool
    {
        if ($alias === '') {
            return false;
        }
        $filename = $this->alias->get($alias);
        return is_writable($filename);
    }
}
