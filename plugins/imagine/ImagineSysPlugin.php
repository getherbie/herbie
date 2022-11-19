<?php

declare(strict_types=1);

namespace herbie\sysplugin\imagine;

use herbie\Alias;
use herbie\Config;
use herbie\Plugin;
use Imagine\Gd\Imagine;
use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\Color;
use Imagine\Image\Point;
use Imagine\Filter\Advanced\RelativeResize;
use Imagine\Filter\Basic\Resize;
use Twig\Markup;

use function herbie\str_trailing_slash;

final class ImagineSysPlugin extends Plugin
{
    protected Config $config;

    protected string $basePath;

    protected string $cachePath;

    private Alias $alias;

    public function __construct(Alias $alias, Config $config)
    {
        $this->alias = $alias;
        $this->config = $config;
        $this->basePath = str_trailing_slash($config->getAsString('urls.web'));
        $this->cachePath = $this->config->getAsString('plugins.imagine.cachePath');
    }

    public function twigFilters(): array
    {
        return [
            ['imagine', [$this, 'imagineFilter']]
        ];
    }

    public function twigFunctions(): array
    {
        return [
            ['imagine', [$this, 'imagineFunction'], ['is_safe' => ['html']]]
        ];
    }

    public function imagineFunction(string $path, string $filter = 'default', array $attribs = []): string
    {
        $abspath = $this->alias->get('@media/' . $path);

        $attribs['class'] = $attribs['class'] ?? 'imagine';

        if (!is_file($abspath)) {
            $attribs['class'] = trim($attribs['class'] . ' imagine--file-not-found');
            return sprintf(
                '<img src="%s"%s>',
                $this->getTransparentOnePixelSrc(),
                $this->buildHtmlAttributes($attribs)
            );
        }

        $sanitizedFilter = $this->sanatizeFilterName($filter);

        $attribs['class'] = trim($attribs['class'] . ' imagine--filter-' . $sanitizedFilter);

        $cachePath = $this->applyFilter($path, $sanitizedFilter);

        $attribs['alt'] = $attribs['alt'] ?? '';

        if (empty($attribs['width']) && empty($attribs['height'])) {
            [$width, $height] = $this->getImageSize($cachePath);
            if (($width > 0) && ($height > 0)) {
                $attribs['width'] = $width;
                $attribs['height'] = $height;
            }
        }

        return sprintf(
            '<img src="%s"%s>',
            $this->basePath . $cachePath,
            $this->buildHtmlAttributes($attribs)
        );
    }

    private function getTransparentOnePixelSrc(): string
    {
        // see http://png-pixel.com
        return 'data:image/png;'
        . 'base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
    }

    /**
     * Gets the browser path for the image and filter to apply.
     */
    public function imagineFilter(string $path, string $filter): Markup
    {
        $abspath = $this->alias->get('@media/' . $path);

        if (!is_file($abspath)) {
            $dataSrc = $this->getTransparentOnePixelSrc();
            return new Markup($dataSrc, 'utf8');
        }

        $sanatizedFilter = $this->sanatizeFilterName($filter);

        return new Markup(
            $this->basePath . $this->applyFilter($path, $sanatizedFilter),
            'utf8'
        );
    }

    private function sanatizeFilterName(string $filter): string
    {
        if ($filter !== 'default') {
            if ($this->config->check("plugins.imagine.filterSets.{$filter}") === false) {
                $filter = 'default';
            }
        }
        return $filter;
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

    protected function applyFilter(string $relpath, string $filter): string
    {
        $path = $this->alias->get('@media/' . $relpath);

        $filterConfig = $this->config->getAsArray("plugins.imagine.filterSets.{$filter}");
        $cachePath = $this->resolveCachePath($relpath, $filter);

        if (!empty($filterConfig['test'])) {
            if (is_file($cachePath)) {
                unlink($cachePath);
            }
        }

        if (is_file($cachePath)) {
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
        $info = pathinfo($path);
        $dirname = $info['dirname'] ?? '';
        $extension = $info['extension'] ?? '';

        $dirname = trim($dirname, '.');
        if ($dirname !== '') {
            $dirname = '/' . $dirname;
        }

        $dot = $extension !== '' ? '.' : '';

        return sprintf(
            '%s%s/%s-%s%s%s',
            $this->cachePath,
            $dirname,
            $info['filename'],
            $filter,
            $dot,
            $extension
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
            $angle = (int) $options['angle'];
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
            throw new \InvalidArgumentException('Missing min option.');
        }

        [$width, $height] = $options['min'];

        $size = $image->getSize();
        $origWidth = $size->getWidth();
        $origHeight = $size->getHeight();

        if ($origWidth < $width || $origHeight < $height) {
            $widthRatio = $width / $origWidth;
            $heightRatio = $height / $origHeight;

            $ratio = $widthRatio > $heightRatio ? $widthRatio : $heightRatio;

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

    protected function buildHtmlAttributes(array $htmlOptions = []): string
    {
        $attributes = '';
        foreach ($htmlOptions as $key => $value) {
            $attributes .= ' ' . $key . '="' . $value . '"';
        }
        return rtrim($attributes);
    }
}
