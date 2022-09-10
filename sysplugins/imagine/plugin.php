<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie\sysplugin;

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
        $this->basePath = rtrim($config->get('urls.web'), '/') . '/';
        $this->cachePath = $this->config->get('plugins.imagine.cachePath');
    }

    /**
     * @return array[]
     */
    public function twigFilters(): array
    {
        return [
            ['imagine', [$this, 'imagineFilter']]
        ];
    }

    /**
     * @return array[]
     */
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
                $path,
                $this->buildHtmlAttributes($attribs)
            );
        }

        $sanatizedFilter = $this->sanatizeFilterName($filter);

        $attribs['class'] = trim($attribs['class'] . ' imagine--filter-' . $sanatizedFilter);

        $cachePath = $this->applyFilter($path, $sanatizedFilter);

        $attribs['alt'] = $attribs['alt'] ?? '';

        if (empty($attribs['width']) && empty($attribs['height'])) {
            $size = $this->getImageSize($cachePath);
            if (!empty($size[0]) && !empty($size[1])) {
                $attribs['width'] = $size[0];
                $attribs['height'] = $size[1];
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
            return new Markup('', 'utf8');
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
        return getimagesize($cachePath);
    }

    protected function applyFilter(string $relpath, string $filter): string
    {
        $path = $this->alias->get('@media/' . $relpath);

        $filterConfig = $this->config->get("plugins.imagine.filterSets.{$filter}");
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
        return sprintf(
            '%s/%s/%s-%s.%s',
            $this->cachePath,
            $info['dirname'],
            $info['filename'],
            $filter,
            $info['extension']
        );
    }

    protected function applyResizeFilter(ImageInterface $image, array $options): ImageInterface
    {
        // Defaults
        $size = new Box(120, 120);

        if (isset($options['size']) && (count($options['size']) == 2)) {
            list($width, $height) = $options['size'];
            $size = new Box($width, $height);
        }

        return $image->resize($size);
    }
    
    protected function applyThumbnailFilter(ImageInterface $image, array $options): ImageInterface
    {
        // Defaults
        $size = new Box(120, 120);
        $mode = ImageInterface::THUMBNAIL_OUTBOUND;

        if (isset($options['size']) && (count($options['size']) == 2)) {
            list($width, $height) = $options['size'];
            $size = new Box($width, $height);
        }

        if (isset($options['mode']) && ($options['mode'] == 'inset')) {
            $mode = ImageInterface::THUMBNAIL_INSET;
        }

        return $image->thumbnail($size, $mode);
    }
    
    protected function applyCropFilter(ImageInterface $image, array $options): ImageInterface
    {
        // Defaults
        $start = new Point(0, 0);
        $size = new Box(120, 120);

        if (isset($options['start']) && (count($options['start']) == 2)) {
            list($x, $y) = $options['start'];
            $start = new Point($x, $y);
        }

        if (isset($options['size']) && (count($options['size']) == 2)) {
            list($width, $height) = $options['size'];
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

        list($width, $height) = $options['min'];

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
