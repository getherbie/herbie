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
use Twig_Extension;
use Twig_Filter;
use Twig_Function;
use Twig_Test;

class TwigCoreExtension extends Twig_Extension
{
    /** @var Alias */
    private $alias;

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

    /** @var TwigRenderer */
    private $twigRenderer;

    /**
     * TwigExtension constructor.
     * @param Alias $alias
     * @param Assets $assets
     * @param Environment $environment
     * @param SlugGeneratorInterface $slugGenerator
     * @param Translator $translator
     * @param UrlGenerator $urlGenerator
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
            new Twig_Filter('filesize', [$this, 'filterFilesize']),
            new Twig_Filter('filter', [$this, 'filterFilter'], ['is_variadic' => true]),
            new Twig_Filter('slugify', [$this, 'filterSlugify']),
            new Twig_Filter('strftime', [$this, 'filterStrftime']),
            new Twig_Filter('visible', [$this, 'filterVisible'])
        ];
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new Twig_Function('addcss', [$this, 'functionAddCss']),
            new Twig_Function('addjs', [$this, 'functionAddJs']),
            new Twig_Function('download', [$this, 'functionDownload'], [
                'is_safe' => ['html'],
                'needs_context' => true
            ]),
            new Twig_Function('file', [$this, 'functionFile'], ['is_safe' => ['html']]),
            new Twig_Function('image', [$this, 'functionImage'], ['is_safe' => ['html']]),
            new Twig_Function('link', [$this, 'functionLink'], ['is_safe' => ['html']]),
            new Twig_Function('outputcss', [$this, 'functionOutputCss'], ['is_safe' => ['html']]),
            new Twig_Function('outputjs', [$this, 'functionOutputJs'], ['is_safe' => ['html']]),
            new Twig_Function('translate', [$this, 'functionTranslate']),
            new Twig_Function('url', [$this, 'functionUrl']),
            new Twig_Function('mail', [$this, 'functionMail'], ['is_safe' => ['html']])
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
     * @param array $attribs
     * @return string
     */
    private function createLink(string $route, string $label, array $attribs = []): string
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
     * @param \Traversable $iterator
     * @param array $selectors
     * @return array
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

    /**
     * @param PageTree $tree
     * @return PageTreeFilterIterator
     */
    public function filterVisible(PageTree $tree): PageTreeFilterIterator
    {
        $treeIterator = new PageTreeIterator($tree);
        return new PageTreeFilterIterator($treeIterator);
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
     * @param array $context
     * @param string $path
     * @param string $label
     * @param bool $info
     * @param array $attribs
     * @return string
     */
    public function functionDownload(
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

    /**
     * @param string $email
     * @param string $label
     * @param array $attribs
     * @return string
     */
    public function functionMail(string $email, string $label, array $attribs = []): string
    {
        $attribs['class'] = $attribs['class'] ?? 'link__label';

        $replace = [
            '{href}' => $email,
            '{attribs}' => $this->buildHtmlAttributes($attribs),
            '{label}' => $label,
        ];

        $template = '<span class="link link--mailto"><a href="mailto:{href}" {attribs}>{label}</a></span>';
        return strtr($template, $replace);
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
     * @param array $attribs
     * @return string
     */
    public function functionLink(string $route, string $label, array $attribs = []): string
    {
        return $this->createLink($route, $label, $attribs);
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
     * @param bool $absolute
     * @return string
     */
    public function functionUrl(string $route, bool $absolute = false): string
    {
        if ($absolute) {
            return $this->urlGenerator->generateAbsolute($route);
        }
        return $this->urlGenerator->generate($route);
    }

    /**
     * @param string $path
     * @param string $label
     * @param bool $info
     * @param array $attribs
     * @return string
     */
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
