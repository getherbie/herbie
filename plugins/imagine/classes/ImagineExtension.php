<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\plugin\imagine\classes;

use Imagine\Gd\Imagine;
use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\Color;
use Imagine\Image\Point;
use Imagine\Filter\Advanced\RelativeResize;
use Imagine\Filter\Basic\Resize;

class ImagineExtension extends \Twig_Extension
{

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $cachePath = 'cache';

    /**
     * @param Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->basePath = $app['request']->getBasePath() . '/';
        $this->cachePath = $app['config']->get('imagine.cachePath', 'cache');
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        return ['imagine' => new \Twig_Filter_Method($this, 'imagineFilter')];
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('imagine', [$this, 'imagineFunction'], ['is_safe' => ['html']])
        ];
    }

    /**
     * @param string $path
     * @param string $filter
     * @param array $attributes
     * @param string $alt
     * @param string $class
     * @param string $id
     * @param string $style
     * @param string $title
     * @param int $width
     * @param int $height
     * @param int $media
     * @return string
     */
    public function imagineFunction($path, $filter, $attributes = [], $alt = '', $class = '', $id = '', $style= '',
                                    $title = '', $width = 0, $height = 0, $media = 1)
    {
        $cachePath = $this->applyFilter($path, $filter, $media);

        $htmlAttributes = [];

        if(!empty($attributes)) {
            $htmlAttributes = $attributes;
        }

        $htmlAttributes['alt'] = $alt;

        if(!empty($class)) {
            $htmlAttributes['class'] = $class;
        }
        if(!empty($title)) {
            $htmlAttributes['title'] = $title;
        }
        if($width > 0 && $height > 0) {
            $htmlAttributes['width'] = $width;
            $htmlAttributes['height'] = $height;
        } else {
            $size = getimagesize($cachePath);
            $htmlAttributes['width'] = $size[0];
            $htmlAttributes['height'] = $size[1];
        }

        return sprintf(
            '<img src="%s"%s>',
            $this->basePath . $cachePath,
            $this->buildHtmlAttributes($htmlAttributes)
        );
    }

    /**
     * Gets the browser path for the image and filter to apply.
     *
     * @param string $path
     * @param string $filter
     * @param int $media
     * @return \Twig_Markup
     */
    public function imagineFilter($path, $filter, $media = 1)
    {
        return new \Twig_Markup(
            $this->basePath . $this->applyFilter($path, $filter, $media),
            'utf8'
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'imagine';
    }

    /**
     * @param string $path
     * @param string $filter
     * @param int $media
     * @return string
     */
    protected function applyFilter($path, $filter, $media)
    {
        if($media == 1) {
            $path = 'media/' . $path;
        }

        if ($this->app['config']->isEmpty("plugins.config.imagine.filter_sets.{$filter}")) {
            return $path;
        }

        // return original path if file not exists
        if (!is_file($path)) {
            return $path;
        }

        $filterConfig = $this->app['config']->get("plugins.config.imagine.filter_sets.{$filter}");
        $cachePath = $this->resolveCachePath($path, $filter);

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
        extract($info); // $dirname, $filename, $extension
        return sprintf(
            '%s/%s/%s-%s.%s',
            $this->cachePath,
            $dirname,
            $filename,
            $filter,
            $extension
        );
    }

    /**
     * @param \Imagine\Image\ImageInterface $image
     * @param array $options
     * @return \Imagine\Image\ImageInterface
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
     * @param \Imagine\Image\ImageInterface $image
     * @param array $options
     * @return \Imagine\Image\ImageInterface
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
     * @param \Imagine\Image\ImageInterface $image
     * @param array $options
     * @return \Imagine\Image\ImageInterface
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
     * @param \Imagine\Image\ImageInterface $image
     * @return \Imagine\Image\ImageInterface
     */
    protected function applyGrayscaleFilter(ImageInterface $image)
    {
        $image->effects()->grayscale();
        return $image;
    }

    /**
     * @param \Imagine\Image\ImageInterface $image
     * @return \Imagine\Image\ImageInterface
     */
    protected function applyNegativeFilter(ImageInterface $image)
    {
        $image->effects()->negative();
        return $image;
    }

    /**
     * @param \Imagine\Image\ImageInterface $image
     * @return \Imagine\Image\ImageInterface
     */
    protected function applySharpenFilter(ImageInterface $image)
    {
        $image->effects()->sharpen();
        return $image;
    }

    /**
     * @param \Imagine\Image\ImageInterface $image
     * @param array $options
     * @return \Imagine\Image\ImageInterface
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
     * @param \Imagine\Image\ImageInterface $image
     * @param array $options
     * @return \Imagine\Image\ImageInterface
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
     * @param \Imagine\Image\ImageInterface $image
     * @param array $options
     * @return \Imagine\Image\ImageInterface
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
     * @param \Imagine\Image\ImageInterface $image
     * @param array $options
     * @return \Imagine\Image\ImageInterface
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
     * @param \Imagine\Image\ImageInterface $image
     * @return \Imagine\Image\ImageInterface
     */
    protected function applyFlipHorizontallyFilter(ImageInterface $image)
    {
        $image->flipHorizontally();
        return $image;
    }

    /**
     * @param \Imagine\Image\ImageInterface $image
     * @return \Imagine\Image\ImageInterface
     */
    protected function applyFlipVerticallyFilter(ImageInterface $image)
    {
        $image->flipVertically();
        return $image;
    }

    /**
     * @see https://github.com/liip/LiipImagineBundle/blob/master/Imagine/Filter/Loader/UpscaleFilterLoader.php
     * @param \Imagine\Image\ImageInterface $image
     * @param array $options
     * @return \Imagine\Image\ImageInterface
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
     *
     * @param \Imagine\Image\ImageInterface $image
     * @param array $options
     * @return \Imagine\Image\ImageInterface
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
