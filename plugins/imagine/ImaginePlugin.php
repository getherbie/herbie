<?php

declare(strict_types=1);

namespace herbie\plugins\imagine;

use herbie\AbstractPlugin;
use herbie\Alias;
use herbie\Config;
use herbie\Plugin;
use Imagine\Filter\Advanced\RelativeResize;
use Imagine\Filter\Basic\Resize;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Color;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use InvalidArgumentException;
use Twig\Markup;

use function herbie\str_route_from_url;
use function herbie\str_trailing_slash;
use function herbie\str_unleading_slash;

#[Plugin('imagine', requires: ['imagine/imagine' => '^1.3'])]
final class ImaginePlugin extends AbstractPlugin
{
    protected string $basePath;

    protected string $cachePath;

    protected string $cacheUrlPath;

    public function __construct(private readonly Alias $alias, protected Config $config)
    {
        $this->basePath = str_trailing_slash($this->config->getAsString('urls.web'));
        $cachePath = $this->config->getAsString('plugins.imagine.cachePath');
        $this->cacheUrlPath = $this->normalizeCacheUrlPath($cachePath);
        $this->cachePath = rtrim($this->alias->get('@web/' . $this->cacheUrlPath), '/');
    }

    #[\Override]
    public function twigFilters(): array
    {
        return [
            ['imagine', $this->imagineFilter(...)],
        ];
    }

    #[\Override]
    public function twigFunctions(): array
    {
        return [
            ['imagine', $this->imagineFunction(...), ['is_safe' => ['html']]],
        ];
    }

    public function imagineFunction(string $path, string $collection = 'default', array $attribs = []): string
    {
        $source = $this->resolveSource($path);
        $absolutePath = $source['absolute'];

        $attribs['class'] ??= 'imagine';

        if (!is_file($absolutePath)) {
            $attribs['class'] = trim($attribs['class'] . ' imagine--file-not-found');
            return sprintf(
                '<img src="%s"%s>',
                $this->getTransparentOnePixelSrc(),
                $this->buildHtmlAttributes($attribs),
            );
        }

        $sanitizedFilter = $this->sanitizeFilterName($collection);

        $attribs['class'] = trim($attribs['class'] . ' imagine--filter-' . $sanitizedFilter);

        $cachePath = $this->applyFilterSet($source, $sanitizedFilter);

        $attribs['alt'] ??= '';

        if (empty($attribs['width']) && empty($attribs['height'])) {
            [$width, $height] = $this->getImageSize($cachePath);
            if (($width > 0) && ($height > 0)) {
                $attribs['width'] = $width;
                $attribs['height'] = $height;
            }
        }

        $cacheUrl = $this->resolveCacheUrl($source['cachePath'], $sanitizedFilter);

        return sprintf(
            '<img src="%s"%s>',
            $this->basePath . $cacheUrl,
            $this->buildHtmlAttributes($attribs),
        );
    }

    /**
     * Resolves a public path to the source file and its cache key.
     *
     * Like file_url(), a path below the media route is read from @media and
     * every other path is read from the web root. Leading slashes are ignored.
     *
     * @return array{absolute: string, cachePath: string}
     */
    private function resolveSource(string $path): array
    {
        $path = str_unleading_slash(str_replace('\\', '/', $path));
        $route = trim(str_route_from_url(
            $this->config->getAsString('urls.media'),
            $this->config->getAsString('urls.web'),
        ), '/');

        if ($route !== '' && ($path === $route || str_starts_with($path, $route . '/'))) {
            $relative = $path === $route ? '' : substr($path, strlen($route) + 1);
            return [
                'absolute' => $this->alias->get('@media/' . $relative),
                'cachePath' => $relative,
            ];
        }

        return [
            'absolute' => $this->alias->get('@web/' . $path),
            'cachePath' => 'web/' . $path,
        ];
    }

    private function getTransparentOnePixelSrc(): string
    {
        // see http://png-pixel.com
        return 'data:image/png;'
            . 'base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
    }

    protected function buildHtmlAttributes(array $htmlOptions = []): string
    {
        $attributes = '';
        foreach ($htmlOptions as $key => $value) {
            $attributes .= ' ' . $key . '="' . $value . '"';
        }
        return rtrim($attributes);
    }

    private function sanitizeFilterName(string $filterSet): string
    {
        if ($filterSet !== 'default') {
            if ($this->config->check("plugins.imagine.collections.{$filterSet}") === false) {
                $filterSet = 'default';
            }
        }
        return $filterSet;
    }

    /**
     * @param array{absolute: string, cachePath: string} $source
     */
    protected function applyFilterSet(array $source, string $filterSet): string
    {
        $path = $source['absolute'];

        $filterConfig = $this->config->getAsArray("plugins.imagine.collections.{$filterSet}");
        $cachePath = $this->resolveCachePath($source['cachePath'], $filterSet);

        $cache = (bool)($filterConfig['cache'] ?? true);

        if ($cache && is_file($cachePath)) {
            return $cachePath;
        }

        $imagine = new Imagine();
        $image = $imagine->open($path);

        foreach ($filterConfig['filters'] as $key => $value) {
            $methodName = sprintf('apply%sFilter', ucfirst($key));
            if (method_exists($this, $methodName)) {
                $image = $this->$methodName($image, $value);
            }
        }

        $options = [];
        if (isset($filterConfig['quality'])) {
            $options['quality'] = $filterConfig['quality'];
        }

        $cacheDir = dirname($cachePath);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $image->save($cachePath, $options);
        return $cachePath;
    }

    protected function resolveCachePath(string $path, string $filter): string
    {
        return $this->cachePath . '/' . $this->buildCacheFilePath($path, $filter);
    }

    protected function resolveCacheUrl(string $path, string $filter): string
    {
        return $this->cacheUrlPath . '/' . $this->buildCacheFilePath($path, $filter);
    }

    private function buildCacheFilePath(string $path, string $filter): string
    {
        $info = pathinfo($path);
        $dirname = trim((string)($info['dirname'] ?? ''), '.');
        $extension = (string)($info['extension'] ?? '');
        $dot = $extension !== '' ? '.' : '';
        $filename = sprintf('%s-%s%s%s', $info['filename'], $filter, $dot, $extension);

        if ($dirname === '') {
            return $filename;
        }

        return $dirname . '/' . $filename;
    }

    private function normalizeCacheUrlPath(string $cachePath): string
    {
        $normalized = str_replace('\\', '/', trim($cachePath));
        if ($normalized === '' || $normalized === '/') {
            throw new InvalidArgumentException(
                'Imagine cachePath must be a web-relative path like "cache/imagine".',
            );
        }
        if (preg_match('#^[A-Za-z]:/#', $normalized) === 1 || str_starts_with($normalized, '/')) {
            throw new InvalidArgumentException(
                'Imagine cachePath must be a web-relative path like "cache/imagine".',
            );
        }

        $parts = [];
        foreach (explode('/', $normalized) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                if ($parts === []) {
                    throw new InvalidArgumentException(
                        'Imagine cachePath must not contain path traversal segments.',
                    );
                }
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }

        if ($parts === []) {
            throw new InvalidArgumentException(
                'Imagine cachePath must be a web-relative path like "cache/imagine".',
            );
        }

        return implode('/', $parts);
    }

    private function getImageSize(string $cachePath): array
    {
        if (!is_file($cachePath)) {
            return [];
        }
        $size = getimagesize($cachePath);
        if ($size === false) {
            return [0, 0];
        }
        return [$size[0], $size[1]];
    }

    /**
     * Gets the browser path for the image and filter to apply.
     */
    public function imagineFilter(string $path, string $collection = 'default'): Markup
    {
        $source = $this->resolveSource($path);
        $absolutePath = $source['absolute'];

        if (!is_file($absolutePath)) {
            $dataSrc = $this->getTransparentOnePixelSrc();
            return new Markup($dataSrc, 'utf8');
        }

        $sanitizedFilterSet = $this->sanitizeFilterName($collection);

        $this->applyFilterSet($source, $sanitizedFilterSet);

        return new Markup(
            $this->basePath . $this->resolveCacheUrl($source['cachePath'], $sanitizedFilterSet),
            'utf8',
        );
    }

    protected function applyResizeFilter(ImageInterface $image, array $options): ImageInterface
    {
        // Defaults
        $size = new Box(120, 120);

        if (isset($options['size']) && (count($options['size']) === 2)) {
            [$width, $height] = $options['size'];
            $size = new Box($width, $height);
        }

        return $image->resize($size);
    }

    protected function applyThumbnailFilter(ImageInterface $image, array $options): ImageInterface
    {
        // Defaults
        $size = new Box(120, 120);
        $mode = ImageInterface::THUMBNAIL_OUTBOUND;

        if (isset($options['size']) && (count($options['size']) === 2)) {
            [$width, $height] = $options['size'];
            $size = new Box($width, $height);
        }

        if (isset($options['mode']) && ($options['mode'] === 'inset')) {
            $mode = ImageInterface::THUMBNAIL_INSET;
        }

        return $image->thumbnail($size, $mode);
    }

    protected function applyCropFilter(ImageInterface $image, array $options): ImageInterface
    {
        // Defaults
        $start = new Point(0, 0);
        $size = new Box(120, 120);

        if (isset($options['start']) && (count($options['start']) === 2)) {
            [$x, $y] = $options['start'];
            $start = new Point($x, $y);
        }

        if (isset($options['size']) && (count($options['size']) === 2)) {
            [$width, $height] = $options['size'];
            $size = new Box($width, $height);
        }

        return $image->crop($start, $size);
    }

    protected function applyGrayscaleFilter(ImageInterface $image): ImageInterface
    {
        $image->effects()->grayscale();
        return $image;
    }

    protected function applyNegativeFilter(ImageInterface $image): ImageInterface
    {
        $image->effects()->negative();
        return $image;
    }

    protected function applySharpenFilter(ImageInterface $image): ImageInterface
    {
        $image->effects()->sharpen();
        return $image;
    }

    protected function applyBlurFilter(ImageInterface $image, array $options): ImageInterface
    {
        $sigma = 1;
        if (isset($options['sigma'])) {
            $sigma = $options['sigma'];
        }
        $image->effects()->blur($sigma);
        return $image;
    }

    protected function applyGammaFilter(ImageInterface $image, array $options): ImageInterface
    {
        $correction = 0.5;
        if (isset($options['correction'])) {
            $correction = $options['correction'];
        }
        $image->effects()->gamma($correction);
        return $image;
    }

    protected function applyColorizeFilter(ImageInterface $image, array $options): ImageInterface
    {
        if (isset($options['color'])) {
            $color = $image->palette()->color($options['color']);
            $image->effects()->colorize($color);
        }
        return $image;
    }

    protected function applyRotateFilter(ImageInterface $image, array $options): ImageInterface
    {
        if (isset($options['angle'])) {
            $angle = (int)$options['angle'];
            $image->rotate($angle);
        }
        return $image;
    }

    protected function applyFlipHorizontallyFilter(ImageInterface $image): ImageInterface
    {
        $image->flipHorizontally();
        return $image;
    }

    protected function applyFlipVerticallyFilter(ImageInterface $image): ImageInterface
    {
        $image->flipVertically();
        return $image;
    }

    /**
     * @see https://github.com/liip/LiipImagineBundle/blob/master/Imagine/Filter/Loader/UpscaleFilterLoader.php
     */
    protected function applyUpscaleFilter(ImageInterface $image, array $options): ImageInterface
    {
        if (!isset($options['min'])) {
            throw new InvalidArgumentException('Missing min option.');
        }

        [$width, $height] = $options['min'];

        $size = $image->getSize();
        $origWidth = $size->getWidth();
        $origHeight = $size->getHeight();

        if ($origWidth < $width || $origHeight < $height) {
            $widthRatio = $width / $origWidth;
            $heightRatio = $height / $origHeight;

            $ratio = max($widthRatio, $heightRatio);

            $filter = new Resize(new Box($origWidth * $ratio, $origHeight * $ratio));

            return $filter->apply($image);
        }

        return $image;
    }

    protected function applyRelativeResizeFilter(ImageInterface $image, array $options): ImageInterface
    {
        $method = $options['method'];
        $parameter = $options['parameter'];
        $filter = new RelativeResize($method, $parameter);
        return $filter->apply($image);
    }
}
