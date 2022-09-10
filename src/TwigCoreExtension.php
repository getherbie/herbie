<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

use Ausi\SlugGenerator\SlugGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class TwigCoreExtension extends AbstractExtension
{
    private Alias $alias;

    private UrlGenerator $urlGenerator;

    private Translator $translator;

    private SlugGeneratorInterface $slugGenerator;

    private Assets $assets;

    private Environment $environment;

    private TwigRenderer $twigRenderer;

    /**
     * TwigExtension constructor.
     */
    public function __construct(
        Alias $alias,
        Assets $assets,
        Environment $environment,
        SlugGeneratorInterface $slugGenerator,
        Translator $translator,
        UrlGenerator $urlGenerator
    ) {
        $this->alias = $alias;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
        $this->slugGenerator = $slugGenerator;
        $this->assets = $assets;
        $this->environment = $environment;
    }

    public function setTwigRenderer(TwigRenderer $twigRenderer): void
    {
        $this->twigRenderer = $twigRenderer;
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
            new TwigFilter('visible', [$this, 'filterVisible'])
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
        return str_replace(',', '.', round($size, 1)) . ' ' . $units[$i];
    }

    /**
     * @throws \Exception
     * @todo Implement und document this twig filter
     */
    public function filterFilter(\Traversable $iterator, array $selectors = []): array
    {
        $selector = new Selector(get_class($iterator));
        $items = $iterator->getItems();
        $filtered = $selector->find($selectors, $items);
        return $filtered;
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
            $date = date('Y-m-d H:i:s', $date);
        }
        $dateTime = new \DateTime($date);
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

        // get config from download middleware
        $config = $context['config']['components']['downloadMiddleware'];
        $baseUrl = rtrim($config['baseUrl'], '/') . '/';
        $storagePath = rtrim($config['storagePath'], '/') . '/';

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

    public function functionTranslate(string $category, string $message, array $params = []): string
    {
        return $this->translator->translate($category, $message, $params);
    }

    public function functionUrl(string $route, bool $absolute = false): string
    {
        if ($absolute) {
            return $this->urlGenerator->generateAbsolute($route);
        }
        return $this->urlGenerator->generate($route);
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
