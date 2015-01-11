<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\plugin\googlemaps;

use Herbie;
use Twig_SimpleFunction;

class GooglemapsPlugin extends Herbie\Plugin
{
    /**
     * @var int
     */
    private static $instances = 0;

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @param Herbie\Event $event
     */
    public function onTwigInitialized(Herbie\Event $event)
    {
        $this->twig = $event['twig'];
        $this->twig->addFunction(
            new Twig_SimpleFunction('googlemaps', [$this, 'googleMaps'], ['is_safe' => ['html']])
        );
    }

    /**
     * @param string $id
     * @param int $width
     * @param int $height
     * @param string $type
     * @param string $class
     * @param int $zoom
     * @param string $address
     * @return string
     */
    public function googleMaps($id = 'gmap', $width = 600, $height = 450, $type = 'roadmap', $class = 'gmap', $zoom = 15, $address = '')
    {
        self::$instances++;
        $template = $this->app['config']->get(
            'plugins.config.googlemaps.template',
            '@plugin/googlemaps/templates/googlemaps.twig'
        );
        return $this->twig->render($template, [
                'id' => $id . '-' . self::$instances,
                'width' => $width,
                'height' => $height,
                'type' => $type,
                'class' => $class,
                'zoom' => $zoom,
                'address' => $address,
                'instances' => self::$instances
        ]);
    }
}
