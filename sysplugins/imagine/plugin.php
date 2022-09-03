<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use herbie\Alias;
use herbie\Config;
use herbie\Plugin;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Exception\OutOfBoundsException;
use Imagine\Exception\RuntimeException;
use Imagine\Gd\Imagine;
use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\Color;
use Imagine\Image\Point;
use Imagine\Filter\Advanced\RelativeResize;
use Imagine\Filter\Basic\Resize;
use Twig\Markup;

class ImagineSysPlugin extends Plugin
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $cachePath;
    /**
     * @var Alias
     */
    private $alias;

    /**
     * @param Alias $alias
     * @param Config $config
     */
    public function __construct(Alias $alias, Config $config)
    {
        $this->alias = $alias;
        $this->config = $config;
        $this->basePath = rtrim($config->get('urls.web'), '/') . '/';
        $this->cachePath = $this->config->get('plugins.imagine.cachePath');
    }

    /**
     * @return array
     */
    public function twigFilters(): array
    {
        return [
            ['imagine', [$this, 'imagineFilter']]
        ];
    }

    /**
     * @return array
     */
    public function twigFunctions(): array
    {
        return [
            ['imagine', [$this, 'imagineFunction'], ['is_safe' => ['html']]]
        ];
    }

    /**
     * @param string $path
     * @param string $filter
     * @param array $attribs
     * @return string
     * @throws RuntimeException
     */
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

    private function getTransparentOnePixelSrc()
    {
        // see http://png-pixel.com
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
    }

    /**
     * Gets the browser path for the image and filter to apply.
     *
     * @param string $path
     * @param string $filter
     * @return Markup
     * @throws RuntimeException
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

    private function sanatizeFilterName($filter)
    {
        if ($filter !== 'default') {
            if ($this->config->check("plugins.imagine.filterSets.{$filter}") === false) {
                $filter = 'default';
            }
        }
        return $filter;
    }

    /**
     * @param string $cachePath
     * @return array
     */
    private function getImageSize(string $cachePath): array
    {
        if (!is_file($cachePath)) {
            return [];
        }
        return getimagesize($cachePath);
    }

    /**
     * @param string $relpath
     * @param string $filter
     * @return string
     * @throws RuntimeException
     */
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

    /**
     * @param string $path
     * @param string $filter
     * @return string
     */
    protected function resolveCachePath($path, $filter)
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

    /**
     * @param ImageInterface $image
     * @param array $options
     * @return ImageInterface
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function applyResizeFilter(ImageInterface $image, $options)
    {
        // Defaults
        $size = new Box(120, 120);

        if (isset($options['size']) && (count($options['size']) == 2)) {
            list($width, $height) = $options['size'];
            $size = new Box($width, $height);
        }

        return $image->resize($size);
    }

    /**
     * @param ImageInterface $image
     * @param array $options
     * @return ImageInterface
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function applyThumbnailFilter(ImageInterface $image, $options)
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

    /**
     * @param ImageInterface $image
     * @param array $options
     * @return ImageInterface
     * @throws InvalidArgumentException
     * @throws OutOfBoundsException
     * @throws RuntimeException
     */
    protected function applyCropFilter(ImageInterface $image, $options)
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

    /**
     * @param ImageInterface $image
     * @return ImageInterface
     * @throws RuntimeException
     */
    protected function applyGrayscaleFilter(ImageInterface $image)
    {
        $image->effects()->grayscale();
        return $image;
    }

    /**
     * @param ImageInterface $image
     * @return ImageInterface
     * @throws RuntimeException
     */
    protected function applyNegativeFilter(ImageInterface $image)
    {
        $image->effects()->negative();
        return $image;
    }

    /**
     * @param ImageInterface $image
     * @return ImageInterface
     * @throws RuntimeException
     */
    protected function applySharpenFilter(ImageInterface $image)
    {
        $image->effects()->sharpen();
        return $image;
    }

    /**
     * @param ImageInterface $image
     * @param array $options
     * @return ImageInterface
     * @throws RuntimeException
     */
    protected function applyBlurFilter(ImageInterface $image, $options)
    {
        $sigma = 1;
        if (isset($options['sigma'])) {
            $sigma = $options['sigma'];
        }
        $image->effects()->blur($sigma);
        return $image;
    }

    /**
     * @param ImageInterface $image
     * @param array $options
     * @return ImageInterface
     * @throws RuntimeException
     */
    protected function applyGammaFilter(ImageInterface $image, $options)
    {
        $correction = 0.5;
        if (isset($options['correction'])) {
            $correction = $options['correction'];
        }
        $image->effects()->gamma($correction);
        return $image;
    }

    /**
     * @param ImageInterface $image
     * @param array $options
     * @return ImageInterface
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function applyColorizeFilter(ImageInterface $image, $options)
    {
        if (isset($options['color'])) {
            $color = $image->palette()->color($options['color']);
            $image->effects()->colorize($color);
        }
        return $image;
    }

    /**
     * @param ImageInterface $image
     * @param array $options
     * @return ImageInterface
     * @throws RuntimeException
     */
    protected function applyRotateFilter(ImageInterface $image, $options)
    {
        if (isset($options['angle'])) {
            $angle = (int) $options['angle'];
            $image->rotate($angle);
        }
        return $image;
    }

    /**
     * @param ImageInterface $image
     * @return ImageInterface
     * @throws RuntimeException
     */
    protected function applyFlipHorizontallyFilter(ImageInterface $image)
    {
        $image->flipHorizontally();
        return $image;
    }

    /**
     * @param ImageInterface $image
     * @return ImageInterface
     * @throws RuntimeException
     */
    protected function applyFlipVerticallyFilter(ImageInterface $image)
    {
        $image->flipVertically();
        return $image;
    }

    /**
     * @see https://github.com/liip/LiipImagineBundle/blob/master/Imagine/Filter/Loader/UpscaleFilterLoader.php
     * @param ImageInterface $image
     * @param array $options
     * @return ImageInterface
     * @throws \InvalidArgumentException
     */
    protected function applyUpscaleFilter(ImageInterface $image, $options)
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

    /**
     * @param ImageInterface $image
     * @param array $options
     * @return ImageInterface
     * @throws InvalidArgumentException
     */
    protected function applyRelativeResizeFilter(ImageInterface $image, $options)
    {
        $method = $options['method'];
        $parameter = $options['parameter'];
        $filter = new RelativeResize($method, $parameter);
        return $filter->apply($image);
    }

    /**
     * @param array $htmlOptions
     * @return string
     */
    protected function buildHtmlAttributes($htmlOptions = [])
    {
        $attributes = '';
        foreach ($htmlOptions as $key => $value) {
            $attributes .= ' ' . $key . '="' . $value . '"';
        }
        return rtrim($attributes);
    }
}
