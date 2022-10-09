<?php

declare(strict_types=1);

namespace herbie\sysplugin\twig_core;

use Ausi\SlugGenerator\SlugGenerator;
use Ausi\SlugGenerator\SlugGeneratorInterface;
use herbie\Alias;
use herbie\Assets;
use herbie\Config;
use herbie\Environment;
use herbie\PageTree;
use herbie\PageTreeFilterIterator;
use herbie\PageTreeIterator;
use herbie\Selector;
use herbie\Translator;
use herbie\TwigRenderer;
use herbie\UrlGenerator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

final class TwigCoreExtension extends AbstractExtension
{
    private Alias $alias;

    private Assets $assets;

    private Environment $environment;

    private SlugGenerator $slugGenerator;

    private Translator $translator;

    private TwigRenderer $twigRenderer;

    private UrlGenerator $urlGenerator;

    /**
     * TwigExtension constructor.
     */
    public function __construct(
        Alias $alias,
        Assets $assets,
        Environment $environment,
        SlugGeneratorInterface $slugGenerator,
        Translator $translator,
        TwigRenderer $twigRenderer,
        UrlGenerator $urlGenerator
    ) {
        $this->alias = $alias;
        $this->assets = $assets;
        $this->environment = $environment;
        $this->slugGenerator = $slugGenerator;
        $this->translator = $translator;
        $this->twigRenderer = $twigRenderer;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('filesize', [$this, 'filterFilesize']),
            new TwigFilter('filter', [$this, 'filterFilter'], ['is_variadic' => true]),
            new TwigFilter('slugify', [$this, 'filterSlugify']),
            new TwigFilter('strftime', [$this, 'filterStrftime']),
            new TwigFilter('visible', [$this, 'filterVisible'], ['deprecated' => true]) // doesn't work properly
        ];
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('add_css', [$this, 'functionAddCss']),
            new TwigFunction('add_js', [$this, 'functionAddJs']),
            new TwigFunction('file_link', [$this, 'functionFileLink'], [
                'is_safe' => ['html'],
                'needs_context' => true
            ]),
            #new TwigFunction('file', [$this, 'functionFile'], ['is_safe' => ['html']]),
            new TwigFunction('image', [$this, 'functionImage'], ['is_safe' => ['html']]),
            new TwigFunction('page_link', [$this, 'functionPageLink'], ['is_safe' => ['html']]),
            new TwigFunction('output_css', [$this, 'functionOutputCss'], ['is_safe' => ['html']]),
            new TwigFunction('output_js', [$this, 'functionOutputJs'], ['is_safe' => ['html']]),
            new TwigFunction('translate', [$this, 'functionTranslate']),
            new TwigFunction('url', [$this, 'functionUrl']),
            new TwigFunction('abs_url', [$this, 'functionAbsUrl']),
            new TwigFunction('mail_link', [$this, 'functionMailLink'], ['is_safe' => ['html']])
        ];
    }

    /**
     * @return TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest('readable', [$this, 'testIsReadable']),
            new TwigTest('writable', [$this, 'testIsWritable'])
        ];
    }

    private function buildHtmlAttributes(array $htmlOptions = []): string
    {
        $attributes = '';
        foreach ($htmlOptions as $key => $value) {
            $attributes .= $key . '="' . $value . '" ';
        }
        return trim($attributes);
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
     * @throws \Exception
     * @todo Implement und document this twig filter
     */

    /**
     * @param \iterable $iterator
     * @param string[] $selectors
     * @return array
     * @throws \Exception
     */
    public function filterFilter(iterable $iterator, array $selectors = []): array
    {
        $selector = new Selector();
        // TODO check for Traversable too
        $data = $iterator instanceof \IteratorAggregate
            ? (array)$iterator->getIterator()
            : $iterator;
        return $selector->find($selectors, $data);
    }

    /**
     * Creates a web friendly URL (slug) from a string.
     */
    public function filterSlugify(string $url): string
    {
        return $this->slugGenerator->generate($url);
    }

    /**
     * @throws \Exception
     */
    public function filterStrftime(string $date, string $format = '%x'): string
    {
        // timestamp?
        if (is_numeric($date)) {
            $date = date('Y-m-d H:i:s', (int)$date);
        }
        try {
            $dateTime = new \DateTime($date);
        } catch (\Exception $e) {
            return $date;
        }
        return strftime($format, $dateTime->getTimestamp());
    }

    public function filterVisible(PageTree $tree): PageTreeFilterIterator
    {
        $treeIterator = new PageTreeIterator($tree);
        return new PageTreeFilterIterator($treeIterator);
    }

    /**
     * @param array|string $paths
     */
    public function functionAddCss(
        $paths,
        array $attr = [],
        ?string $group = null,
        bool $raw = false,
        int $pos = 1
    ): void {
        $this->assets->addCss($paths, $attr, $group, $raw, $pos);
    }

    /**
     * @param array|string $paths
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

    public function functionFileLink(
        array $context,
        string $path,
        string $label = '',
        bool $info = false,
        array $attribs = []
    ): string {
        $attribs['alt'] = $attribs['alt'] ?? '';
        $attribs['class'] = $attribs['class'] ?? 'link__label';

        /** @var Config $config from download middleware */
        $config = $context['config'];
        $baseUrl = rtrim($config->getAsString('components.downloadMiddleware.baseUrl'), '/') . '/';
        $storagePath = rtrim($config->getAsString('components.downloadMiddleware.storagePath'), '/') . '/';

        // combine url and path
        $href = $baseUrl . $path;
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
        return strtr('<span class="link link--download"><a href="{href}" {attribs}>{label}</a>{info}</span>', $replace);
    }

    public function functionMailLink(string $email, ?string $label = null, array $attribs = [], string $template = '@snippet/mail_link.twig'): string
    {
        $attribs['href'] = 'mailto:' . $email;
        $attribs['class'] = $attribs['class'] ?? 'link__label';

        ksort($attribs);

        $context = [
            'attribs' => $attribs,
            'label' => $label ?? $email,
        ];

        return $this->twigRenderer->renderTemplate($template, $context);
    }

    public function functionOutputCss(?string $group = null): string
    {
        return $this->assets->outputCss($group);
    }

    public function functionOutputJs(?string $group = null): string
    {
        return $this->assets->outputJs($group);
    }

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

    public function functionPageLink(string $route, string $label, array $attribs = []): string
    {
        $scheme = parse_url($route, PHP_URL_SCHEME);
        if (is_null($scheme)) {
            $class = 'link--internal';
            $href = $this->urlGenerator->generate($route);
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

    public function functionTranslate(string $category = '', string $message = '', array $params = []): string
    {
        return $this->translator->translate($category, $message, $params);
    }

    public function functionUrl(string $route = ''): string
    {
        return $this->urlGenerator->generate($route);
    }

    public function functionAbsUrl(string $route = ''): string
    {
        return $this->urlGenerator->generateAbsolute($route);
    }

    public function functionFile(string $path, string $label = '', bool $info = false, array $attribs = []): string
    {
        $attribs['alt'] = $attribs['alt'] ?? '';
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

    public function testIsReadable(string $alias): bool
    {
        if (!is_string($alias) || empty($alias)) {
            return false;
        }
        $filename = $this->alias->get($alias);
        return is_readable($filename);
    }

    public function testIsWritable(string $alias): bool
    {
        if (!is_string($alias) || empty($alias)) {
            return false;
        }
        $filename = $this->alias->get($alias);
        return is_writable($filename);
    }
}
