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
     * @var Twig_Environment
     */
    private $twig;

    public function onTwigInitialized(Herbie\Event $event)
    {
        $this->twig = $event['twig'];
        $this->twig->addFunction(
            new Twig_SimpleFunction('googlemaps', array($this, 'googleMaps'), ['is_safe' => ['html']])
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
        static $instances = 0;
        $instances++;
        return $this->twig->render('@plugins/googlemaps/templates/googlemaps.twig', array(
                'id' => $id . '-' . $instances,
                'width' => $width,
                'height' => $height,
                'type' => $type,
                'class' => $class,
                'zoom' => $zoom,
                'address' => $address,
                'instances' => $instances
        ));
    }
}
