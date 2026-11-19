<?php

declare(strict_types=1);

namespace herbie\plugins\twig;

use Ausi\SlugGenerator\SlugGenerator;
use Exception;
use herbie\Alias;
use herbie\Assets;
use herbie\Config;
use herbie\Page;
use herbie\PageList;
use herbie\PageRepositoryInterface;
use herbie\PageTree;
use herbie\Pagination;
use herbie\QueryBuilder;
use herbie\Site;
use herbie\Translator;
use herbie\UrlManager;
use Traversable;
use Twig\Environment;
use Twig\Runtime\EscaperRuntime;
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
    /**
     * TwigExtension constructor.
     */
    public function __construct(private readonly Alias $alias, private readonly Assets $assets, private readonly Config $config, private readonly Environment $environment, private readonly PageRepositoryInterface $pageRepository, private readonly SlugGenerator $slugGenerator, private readonly Translator $translator, private readonly UrlManager $urlManager) {}

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_size', $this->filterFilesize(...)),
            new TwigFilter('slugify', $this->filterSlugify(...)),
            new TwigFilter('spaceless', $this->filterSpaceless(...), ['pre_escape' => 'html', 'is_safe' => ['html']]),
            new TwigFilter('twigify', $this->twigify(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('attributes', $this->attributes(...), ['is_safe' => ['html']]),
            new TwigFunction('css_add', $this->cssAdd(...)),
            new TwigFunction('css_classes', $this->cssClasses(...), ['needs_context' => true]),
            new TwigFunction('css_out', $this->cssOut(...), ['is_safe' => ['html']]),
            new TwigFunction('image', $this->image(...), ['is_safe' => ['html']]),
            new TwigFunction('js_add', $this->jsAdd(...)),
            new TwigFunction('js_out', $this->jsOut(...), ['is_safe' => ['html']]),
            new TwigFunction('link_file', $this->linkFile(...), ['is_safe' => ['html']]),
            new TwigFunction('link_mail', $this->linkMail(...), ['is_safe' => ['html']]),
            new TwigFunction('link_media', $this->linkMedia(...), ['is_safe' => ['html']]),
            new TwigFunction('link_page', $this->linkPage(...), ['is_safe' => ['html']]),
            new TwigFunction('menu_ascii_tree', $this->menuAsciiTree(...), ['is_safe' => ['html']]),
            new TwigFunction('menu_breadcrumb', $this->menuBreadcrumb(...), ['is_safe' => ['html']]),
            new TwigFunction('menu_list', $this->menuList(...), ['is_safe' => ['html']]),
            new TwigFunction('menu_pager', $this->menuPager(...), ['is_safe' => ['html']]),
            new TwigFunction('menu_sitemap', $this->menuSitemap(...), ['is_safe' => ['html']]),
            new TwigFunction('menu_tree', $this->menuTree(...), ['is_safe' => ['html']]),
            new TwigFunction('page_title', $this->pageTitle(...), ['needs_context' => true]),
            new TwigFunction('query', $this->query(...)),
            new TwigFunction('snippet', $this->snippet(...), ['is_safe' => ['all']]),
            new TwigFunction('translate', $this->translate(...)),
            new TwigFunction('twigify', $this->twigify(...), ['is_safe' => ['html']]),
            new TwigFunction('url_rel', $this->urlRelative(...)),
            new TwigFunction('url_abs', $this->urlAbsolute(...)),
        ];
    }

    /**
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest('file_readable', $this->testIsReadable(...)),
            new TwigTest('file_writable', $this->testIsWritable(...)),
        ];
    }

    /**
     * Creates a web friendly URL (slug) from a string.
     */
    public function filterSlugify(string $url): string
    {
        return $this->slugGenerator->generate($url);
    }

    /**
     * Removes whitespace between HTML tags.
     *
     * Replacement for the Twig `spaceless` filter, which is deprecated since Twig 3.12.
     * The `pre_escape` and `is_safe` flags must match the original filter to stay XSS-safe.
     */
    public function filterSpaceless(?string $content): string
    {
        return trim(preg_replace('/>\s+</', '><', $content ?? '') ?? '');
    }

    public function cssAdd(
        array|string $paths,
        array $attribs = [],
        ?string $group = null,
        bool $raw = false,
        int $pos = 1,
    ): void {
        $this->assets->addCss($paths, $attribs, $group, $raw, $pos);
    }

    public function jsAdd(
        array|string $paths,
        array $attribs = [],
        ?string $group = null,
        bool $raw = false,
        int $pos = 1,
    ): void {
        $this->assets->addJs($paths, $attribs, $group, $raw, $pos);
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

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function linkMedia(
        string $path,
        string $label = '',
        array $attribs = [],
        bool $info = false,
        string $template = '@snippet/link_media.twig',
    ): string {
        $attribs['alt'] ??= '';
        $attribs['class'] ??= 'link__label';

        $baseUrl = str_trailing_slash($this->config->getAsString('components.downloadMiddleware.route'));
        $storagePath = str_trailing_slash($this->config->getAsString('components.downloadMiddleware.storagePath'));

        // combine url and path
        $href = $this->urlManager->createUrl($baseUrl . $path);
        $resolvedPath = $this->alias->get($storagePath . $path);

        $fileInfo = $info ? $this->getFileInfo($resolvedPath) : '';
        $label = $label === '' ? basename($resolvedPath) : $label;

        return $this->environment->render($template, [
            'href' => $href,
            'attribs' => $attribs,
            'label' => $label,
            'file_info' => $fileInfo,
        ]);
    }

    private function getFileInfo(string $path): string
    {
        if (!is_readable($path)) {
            return '';
        }
        $replace = [
            '{size}' => $this->filterFilesize(file_size($path)),
            '{extension}' => strtoupper(pathinfo($path, PATHINFO_EXTENSION)),
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
     * Renders an associative array of HTML attributes into a single string.
     *
     * Available escaping strategies (passed as second argument):
     * - 'html'              : htmlspecialchars (quotes, <, >, &)
     * - 'html_attr'         : strict (default; encodes everything except [a-zA-Z0-9,._-])
     * - 'html_attr_relaxed' : like html_attr but also allows :, @, [, ] (useful for URLs/emails)
     * - 'js', 'css', 'url'  : other Twig strategies
     *
     * @see https://twig.symfony.com/doc/3.x/filters/escape.html
     */
    public function attributes(array $attribs = [], string $strategy = 'html_attr'): string
    {
        $result = '';
        foreach ($attribs as $key => $value) {
            $escaped = $this->environment->getRuntime(EscaperRuntime::class)->escape((string)$value, $strategy);
            $result .= ' ' . $key . '="' . $escaped . '"';
        }
        return $result;
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function linkMail(
        string $email,
        ?string $label = null,
        array $attribs = [],
        string $template = '@snippet/link_mail.twig',
    ): string {
        $attribs['href'] = 'mailto:' . $email;
        $attribs['class'] ??= 'link__label';

        ksort($attribs);

        $context = [
            'attribs' => $attribs,
            'label' => $label ?? $email,
        ];

        return $this->environment->render($template, $context);
    }

    public function menuAsciiTree(
        string $route = '',
        int $depth = -1,
        bool $hidden = false,
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
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function menuBreadcrumb(
        string $delim = '',
        array|string $home = '',
        bool $reverse = false,
        string $template = '@snippet/menu_breadcrumb.twig',
    ): string {
        // TODO use string type for param $homeLink (like "route|label")

        $items = [];

        if (!empty($home)) {
            if (is_array($home)) {
                $route = reset($home);
                $label = $home[1] ?? 'Home';
            } else {
                $route = $home;
                $label = 'Home';
            }
            $items[] = [
                'route' => $route,
                'label' => $label,
                'url' => $this->urlManager->createUrl($route),
            ];
        }

        [$route] = $this->urlManager->parseRequest();
        $pageTrail = $this->pageRepository->findAll()->getPageTrail($route);
        foreach ($pageTrail as $item) {
            $itemRoute = $item->getRoute();
            $items[] = [
                'route' => $itemRoute,
                'label' => $item->getMenuTitle(),
                'url' => $this->urlManager->createUrl($itemRoute),
            ];
        }

        if (!empty($reverse)) {
            $items = array_reverse($items);
        }

        return $this->environment->render($template, [
            'items' => $items,
            'delim' => $delim,
        ]);
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function menuList(
        array|string $where = '',
        string $order = '',
        bool $shuffle = false,
        int $limit = 10,
        string $template = '@snippet/menu_list.twig',
    ): string {
        $pageList = $this->pageRepository->findAll();

        if ($shuffle) {
            $pageList = $pageList->shuffle();
        }

        $queryBuilder = $pageList->query();

        if (!empty($where)) {
            $queryBuilder = $queryBuilder->where($where);
        }

        if (!empty($order)) {
            $queryBuilder = $queryBuilder->order($order);
        }

        $items = $queryBuilder->all();
        $totalItems = $queryBuilder->count();

        // sanitize limit
        $limit = $limit < 1 ? $totalItems : $limit;
        $limit = min(max($limit, 1), $totalItems);

        // get sanitized page
        $page = (int)filter_input(INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT);

        $pagination = new Pagination($items, $limit);
        $pagination->setCurrentPage($page);

        return $this->environment->render($template, ['pagination' => $pagination]);
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function menuPager(
        string $prefix = '',
        string $prev_label = '',
        string $next_label = '',
        string $prev_icon = '',
        string $next_icon = '',
        string $class = 'pager',
        string $template = '@snippet/menu_pager.twig',
    ): string {
        [$route] = $this->urlManager->parseRequest();
        $pageList = $this->pageRepository->findAll();

        if ($prefix !== '') {
            $pageList = $pageList->filter(fn($page) => str_starts_with($page->getRoute(), $prefix));
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

        $prev = null;
        if (isset($prevPage)) {
            $effectiveLabel = $prev_label !== '' ? $prev_label : $prevPage->getMenuTitle();
            $prev = [
                'page' => $prevPage,
                'route' => $prevPage->getRoute(),
                'url' => $this->urlManager->createUrl($prevPage->getRoute()),
                'menu_title' => $prevPage->getMenuTitle(),
                'label' => $effectiveLabel,
                'icon' => $prev_icon,
            ];
        }

        $next = null;
        if (isset($nextPage)) {
            $effectiveLabel = $next_label !== '' ? $next_label : $nextPage->getMenuTitle();
            $next = [
                'page' => $nextPage,
                'route' => $nextPage->getRoute(),
                'url' => $this->urlManager->createUrl($nextPage->getRoute()),
                'menu_title' => $nextPage->getMenuTitle(),
                'label' => $effectiveLabel,
                'icon' => $next_icon,
            ];
        }

        return $this->environment->render($template, [
            'class' => $class,
            'prefix' => $prefix,
            'prev' => $prev,
            'next' => $next,
            'prev_label' => $prev_label,
            'next_label' => $next_label,
            'prev_icon' => $prev_icon,
            'next_icon' => $next_icon,
        ]);
    }

    public function menuSitemap(
        string $route = '',
        int $depth = -1,
        bool $hidden = false,
        string $class = 'sitemap',
    ): string {
        return $this->menuTree($route, $depth, $hidden, $class);
    }

    public function menuTree(
        string $route = '',
        int $depth = -1,
        bool $hidden = false,
        string $class = 'menu',
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

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function image(
        string $src,
        array $attribs = [],
        string $template = '@snippet/image.twig',
    ): string {
        $attribs['src'] = $this->urlManager->createUrl('/') . $src;
        $attribs['alt'] ??= '';

        return $this->environment->render($template, [
            'attribs' => $attribs,
        ]);
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function linkPage(
        string $route,
        string $label,
        array $attribs = [],
        string $template = '@snippet/link_page.twig',
    ): string {
        $external = parse_url($route, PHP_URL_SCHEME) !== null;
        if ($external) {
            $class = 'link--external';
            $href = $route;
        } else {
            $class = 'link--page';
            $href = $this->urlManager->createUrl($route);
        }

        $attribs['class'] = trim(($attribs['class'] ?? '') . ' link__label');

        return $this->environment->render($template, [
            'route' => $route,
            'label' => $label,
            'href' => $href,
            'class' => $class,
            'attribs' => $attribs,
            'external' => $external,
        ]);
    }

    /**
     * @param array{site: Site} $context
     */
    public function pageTitle(
        array $context,
        string $delim = ' / ',
        string $site_title = '',
        string $root_title = '',
        bool $reverse = false,
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

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function linkFile(
        string $path,
        string $label = '',
        array $attribs = [],
        bool $info = false,
        string $template = '@snippet/link_file.twig',
    ): string {
        $attribs['class'] ??= 'link__label';
        $label = $label === '' ? basename($path) : $label;
        $fileInfo = $info ? $this->getFileInfo($path) : '';

        return $this->environment->render($template, [
            'path' => $path,
            'href' => $path,
            'label' => $label,
            'info' => $info,
            'file_info' => $fileInfo,
            'attribs' => $attribs,
        ]);
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

    public function twigify(string $content): string
    {
        return $this->environment->render($content);
    }
}
