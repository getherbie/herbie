<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Twig;

use Twig_Extension;
use Imagine\Gd\Imagine;
use Imagine\Image\ImageInterface;
use Imagine\Image\Box;
use Imagine\Image\Point;

class ImagineExtension extends Twig_Extension
{

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        return array(
            'imagine' => new \Twig_Filter_Method($this, 'filter'),
        );
    }

    /**
     * Gets the browser path for the image and filter to apply.
     *
     * @param string $path
     * @param string $filter
     *
     * @return \Twig_Markup
     */
    public function filter($path, $filter)
    {
        return new \Twig_Markup(
            $this->applyFilter($path, $filter),
            'utf8'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'imagine';
    }

    /**
     * @param string $path
     * @param string $filter
     * @return string
     */
    protected function applyFilter($path, $filter)
    {
        $cachePath = str_replace('media/', 'media/cache/' . $filter . '-', $path);
        if (is_file($cachePath)) {
            return $cachePath;
        }

        if (empty($this->app['config']['imagine']['filter_sets'][$filter])) {
            return $path;
        }

        $filterConfig = $this->app['config']['imagine']['filter_sets'][$filter];

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

        $image->save($cachePath, $options);
        return $cachePath;
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
}
