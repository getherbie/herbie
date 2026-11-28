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
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\Runtime\EscaperRuntime;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

use function herbie\file_size;
use function herbie\str_route_from_url;
use function herbie\str_unleading_slash;

final class TwigExtension extends AbstractExtension
{
    /**
     * TwigExtension constructor.
     */
    public function __construct(private readonly Alias $alias, private readonly Assets $assets, private readonly Config $config, private readonly Environment $environment, private readonly PageRepositoryInterface $pageRepository, private readonly SlugGenerator $slugGenerator, private readonly Translator $translator, private readonly UrlManager $urlManager) {}

    /**
     * @return TwigFilter[]
     */
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_bytes', $this->filterBytes(...)),
            new TwigFilter('slugify', $this->filterSlugify(...)),
            new TwigFilter('spaceless', $this->filterSpaceless(...), ['pre_escape' => 'html', 'is_safe' => ['html']]),
            new TwigFilter('twigify', $this->filterTwigify(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * @return TwigFunction[]
     */
    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('html_attributes', $this->htmlAttributes(...), ['is_safe' => ['html']]),
            new TwigFunction('css_add', $this->cssAdd(...)),
            new TwigFunction('css_classes', $this->cssClasses(...), ['needs_context' => true]),
            // @deprecated Since 3.3.0, remove in 4.0: use css_render().
            new TwigFunction('css_out', $this->cssOut(...), ['is_safe' => ['html']]),
            new TwigFunction('css_render', $this->cssRender(...), ['is_safe' => ['html']]),
            new TwigFunction('file_url', $this->fileUrl(...)),
            new TwigFunction('image', $this->image(...), ['is_safe' => ['html']]),
            new TwigFunction('js_add', $this->jsAdd(...)),
            // @deprecated Since 3.3.0, remove in 4.0: use js_render().
            new TwigFunction('js_out', $this->jsOut(...), ['is_safe' => ['html']]),
            new TwigFunction('js_render', $this->jsRender(...), ['is_safe' => ['html']]),
            // @deprecated Since 3.3.0, remove in 4.0: use file_link().
            new TwigFunction('link_file', $this->linkFile(...), ['is_safe' => ['html']]),
            new TwigFunction('file_link', $this->fileLink(...), ['is_safe' => ['html']]),
            // @deprecated Since 3.3.0, remove in 4.0: use mail_link().
            new TwigFunction('link_mail', $this->linkMail(...), ['is_safe' => ['html']]),
            new TwigFunction('mail_link', $this->mailLink(...), ['is_safe' => ['html']]),
            // @deprecated Since 3.3.0, remove in 4.0: use file_link('/media/...', ..., download: true).
            new TwigFunction('link_download', $this->linkDownload(...), ['is_safe' => ['html']]),
            // @deprecated Since 3.3.0, remove in 4.0: use page_link().
            new TwigFunction('link_page', $this->linkPage(...), ['is_safe' => ['html']]),
            new TwigFunction('page_link', $this->pageLink(...), ['is_safe' => ['html']]),
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
            new TwigFunction('twigify', $this->filterTwigify(...), ['is_safe' => ['html']]),
            // @deprecated Since 3.3.0, remove in 4.0: use page_url().
            new TwigFunction('url_rel', $this->urlRelative(...)),
            // @deprecated Since 3.3.0, remove in 4.0: use page_url(absolute: true).
            new TwigFunction('url_abs', $this->urlAbsolute(...)),
            new TwigFunction('page_url', $this->pageUrl(...)),
        ];
    }

    /**
     * @return TwigTest[]
     */
    #[\Override]
    public function getTests(): array
    {
        return [
            new TwigTest('path_readable', $this->isPathReadable(...)),
            new TwigTest('path_writable', $this->isPathWritable(...)),
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
        int $position = 1,
    ): void {
        $this->assets->addCss($paths, $attribs, $group, $raw, $position);
    }

    public function jsAdd(
        array|string $paths,
        array $attribs = [],
        ?string $group = null,
        bool $raw = false,
        int $position = 1,
    ): void {
        $this->assets->addJs($paths, $attribs, $group, $raw, $position);
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

        $theme = trim($this->config->getAsString('theme'));

        $language = 'en';
        if (isset($context['site'])) {
            $language = $context['site']->getLanguage();
        }

        $class = sprintf('page-%s theme-%s layout-%s language-%s', $page, $theme, $layout, $language);
        return str_replace(['/', '.'], '-', $class);
    }

    /**
     * @deprecated Since 3.3.0, remove in 4.0: use file_link('/media/...', ..., download: true).
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function linkDownload(
        string $path,
        string $label = '',
        array $attribs = [],
        bool $show_info = false,
        string $template = '@snippet/link_download.twig',
    ): string {
        $attribs['alt'] ??= '';
        $attribs['class'] ??= 'link__label';

        // downloads are always served from @media, the path is relative to it
        $relative = $this->mediaRelativePath($path);
        $href = $this->fileUrl($this->mediaRoute() . '/' . $relative, download: true);
        $resolvedPath = $this->alias->get('@media/' . $relative);

        $fileInfo = $show_info ? $this->getFileInfo($resolvedPath) : '';
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
            '{size}' => $this->filterBytes(file_size($path)),
            '{extension}' => strtoupper(pathinfo($path, PATHINFO_EXTENSION)),
        ];
        return strtr(' ({extension}, {size})', $replace);
    }

    public function filterBytes(int $size): string
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
     * Attributes with null or false values are omitted, true renders a boolean
     * attribute without a value. Names are escaped with 'html_attr_relaxed'.
     *
     * Available escaping strategies (passed as second argument):
     * - 'html'              : htmlspecialchars (quotes, <, >, &)
     * - 'html_attr'         : strict (default; encodes everything except [a-zA-Z0-9,._-])
     * - 'html_attr_relaxed' : like html_attr but also allows :, @, [, ] (useful for URLs/emails)
     * - 'js', 'css', 'url'  : other Twig strategies
     *
     * @see https://twig.symfony.com/doc/3.x/filters/escape.html
     */
    public function htmlAttributes(array $attribs = [], string $strategy = 'html_attr'): string
    {
        $result = '';
        $escaper = $this->environment->getRuntime(EscaperRuntime::class);
        foreach ($attribs as $key => $value) {
            if (null === $value || false === $value) {
                continue;
            }
            $result .= ' ' . $escaper->escape((string)$key, 'html_attr_relaxed');
            if (true !== $value) {
                $result .= '="' . $escaper->escape((string)$value, $strategy) . '"';
            }
        }
        return $result;
    }

    /**
     * @deprecated Since 3.3.0, remove in 4.0: use mailLink().
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
        return $this->mailLink($email, $label, $attribs, $template);
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function mailLink(
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
     * @param array $home
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function menuBreadcrumb(
        string $delimiter = '',
        array $home = [],
        bool $reverse = false,
        string $template = '@snippet/menu_breadcrumb.twig',
    ): string {
        $items = [];

        if (!empty($home)) {
            $route = reset($home);
            $label = $home[1] ?? 'Home';
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
            'delimiter' => $delimiter,
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

        $pagination = new Pagination($items, $limit);

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
            $pageList = $pageList->filter(fn($page): bool => str_starts_with($page->getRoute(), $prefix));
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
        $htmlTree->setItemCallback(function (PageTree $node): string {
            $menuItem = $node->getMenuItem();
            $href = $this->urlManager->createUrl($menuItem->getRoute());
            return sprintf('<a href="%s">%s</a>', $href, $menuItem->getMenuTitle());
        });

        [$currenRoute] = $this->urlManager->parseRequest();
        return $htmlTree->render($currenRoute);
    }

    /**
     * @deprecated Since 3.3.0, remove in 4.0: use cssRender().
     */
    public function cssOut(?string $group = null, bool $timestamp = false): string
    {
        return $this->cssRender($group, $timestamp);
    }

    public function cssRender(?string $group = null, bool $timestamp = false): string
    {
        return $this->assets->outputCss($group, $timestamp);
    }

    /**
     * @deprecated Since 3.3.0, remove in 4.0: use jsRender().
     */
    public function jsOut(?string $group = null, bool $timestamp = false): string
    {
        return $this->jsRender($group, $timestamp);
    }

    public function jsRender(?string $group = null, bool $timestamp = false): string
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
        $attribs['src'] = $this->fileUrl($src);
        $attribs['alt'] ??= '';

        return $this->environment->render($template, [
            'attribs' => $attribs,
        ]);
    }

    /**
     * Returns the public URL for a file.
     *
     * A path below the media URL (`urls.media`, by default `/media/...`) is served by Herbie from
     * `@media`. Every other path is relative to the web root and served by the web server.
     * External URLs, anchors and query-only values are returned unchanged.
     *
     * `download: true` appends `?download` and forces a download (media files only).
     * `absolute: true` uses the configured origin (`urls.origin`), falling back to the current request.
     */
    public function fileUrl(string $path, bool $download = false, bool $absolute = false): string
    {
        if ($path === '' || preg_match('~^([a-z][a-z0-9+.-]*:|//|[#?])~i', $path) === 1) {
            return $path;
        }

        [$path, $suffix] = $this->splitSuffix(str_replace('\\', '/', $path));
        $path = str_unleading_slash($path);
        $route = $this->mediaRoute();
        $encodedPath = $this->encodePath($path);

        if ($route !== '' && ($path === $route || str_starts_with($path, $route . '/'))) {
            if ($download) {
                $suffix = '?download' . ($suffix !== '' && $suffix[0] === '?' ? '&' . substr($suffix, 1) : $suffix);
            }
            $url = $absolute
                ? $this->urlManager->createAbsoluteUrl($encodedPath)
                : $this->urlManager->createUrl($encodedPath);
            return $url . $suffix;
        }

        $url = $absolute
            ? $this->urlManager->createAbsoluteWebUrl($encodedPath)
            : $this->urlManager->createWebUrl($encodedPath);
        return $url . $suffix;
    }

    /**
     * Returns the route of the public media URL relative to the web URL, e.g. "media".
     */
    private function mediaRoute(): string
    {
        return trim(str_route_from_url(
            $this->config->getAsString('urls.media'),
            $this->config->getAsString('urls.web'),
        ), '/');
    }

    /**
     * Returns a path relative to @media. The media route prefix is optional.
     */
    private function mediaRelativePath(string $path): string
    {
        $path = str_unleading_slash(str_replace('\\', '/', $path));
        $route = $this->mediaRoute();
        if ($route !== '' && str_starts_with($path, $route . '/')) {
            $path = substr($path, strlen($route) + 1);
        }
        return $path;
    }

    /**
     * Returns the file system path for a public file path, e.g. to read its size.
     */
    private function filePath(string $path): string
    {
        $path = str_unleading_slash($this->splitSuffix(str_replace('\\', '/', $path))[0]);
        $route = $this->mediaRoute();
        if ($route !== '' && str_starts_with($path, $route . '/')) {
            return $this->alias->get('@media/' . substr($path, strlen($route) + 1));
        }
        return $this->alias->get('@web/' . $path);
    }

    /**
     * @return array{string, string} The path and the trailing query string or fragment.
     */
    private function splitSuffix(string $path): array
    {
        $pos = strcspn($path, '?#');
        return [substr($path, 0, $pos), substr($path, $pos)];
    }

    private function encodePath(string $path): string
    {
        return implode('/', array_map(rawurlencode(...), explode('/', $path)));
    }

    /**
     * @deprecated Since 3.3.0, remove in 4.0: use pageLink().
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
        return $this->pageLink($route, $label, $attribs, template: $template);
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function pageLink(
        string $route,
        string $label,
        array $attribs = [],
        bool $absolute = false,
        string $template = '@snippet/link_page.twig',
    ): string {
        $external = parse_url($route, PHP_URL_SCHEME) !== null;
        if ($external) {
            $class = 'link--external';
            $href = $route;
        } else {
            $class = 'link--page';
            $href = $absolute
                ? $this->urlManager->createAbsoluteUrl($route)
                : $this->urlManager->createUrl($route);
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
        string $delimiter = ' / ',
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

        return implode($delimiter, $titles);
    }

    public function query(iterable $data): QueryBuilder
    {
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        } else {
            $data = (array)$data;
        }
        return (new QueryBuilder())->from($data);
    }

    public function translate(string $category = '', string $message = '', array $params = []): string
    {
        return $this->translator->translate($category, $message, $params);
    }

    /**
     * @deprecated Since 3.3.0, remove in 4.0: use pageUrl().
     */
    public function urlRelative(string $route = ''): string
    {
        return $this->pageUrl($route);
    }

    /**
     * @deprecated Since 3.3.0, remove in 4.0: use pageUrl(absolute: true).
     */
    public function urlAbsolute(string $route = ''): string
    {
        return $this->pageUrl($route, absolute: true);
    }

    public function pageUrl(string $route = '', bool $absolute = false): string
    {
        return $absolute
            ? $this->urlManager->createAbsoluteUrl($route)
            : $this->urlManager->createUrl($route);
    }

    /**
     * @deprecated Since 3.3.0, remove in 4.0: use fileLink().
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function linkFile(
        string $path,
        string $label = '',
        array $attribs = [],
        bool $show_info = false,
        string $template = '@snippet/link_file.twig',
    ): string {
        return $this->fileLink($path, $label, $attribs, $show_info, template: $template);
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function fileLink(
        string $path,
        string $label = '',
        array $attribs = [],
        bool $show_info = false,
        bool $download = false,
        bool $absolute = false,
        string $template = '@snippet/link_file.twig',
    ): string {
        $attribs['class'] ??= 'link__label';
        $label = $label === '' ? basename($path) : $label;
        $fileInfo = $show_info ? $this->getFileInfo($this->filePath($path)) : '';

        return $this->environment->render($template, [
            'path' => $path,
            'href' => $this->fileUrl($path, download: $download, absolute: $absolute),
            'label' => $label,
            'info' => $show_info,
            'file_info' => $fileInfo,
            'attribs' => $attribs,
        ]);
    }

    public function isPathReadable(string $alias): bool
    {
        if ($alias === '') {
            return false;
        }
        $filename = $this->alias->get($alias);
        return is_readable($filename);
    }

    public function isPathWritable(string $alias): bool
    {
        if ($alias === '') {
            return false;
        }
        $filename = $this->alias->get($alias);
        return is_writable($filename);
    }

    public function filterTwigify(string $content): string
    {
        return $this->environment->render($content);
    }
}
