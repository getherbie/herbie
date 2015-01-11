<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\plugin\vimeo;

use Herbie;
use Twig_SimpleFunction;

class VimeoPlugin extends Herbie\Plugin
{
    /**
     * @var int
     */
    private static $instances = 0;

    /**
     * @var Twig_Environment
     */
    private $twig;

    public function onTwigInitialized(Herbie\Event $event)
    {
        $this->twig = $event['twig'];
        $this->twig->addFunction(
            new Twig_SimpleFunction('vimeo', [$this, 'vimeo'], ['is_safe' => ['html']])
        );
    }

    /**
     * @param string $id
     * @param int $width
     * @param int $height
     * @param int $responsive
     * @return string
     * @see http://embedresponsively.com/
     */
    public function vimeo($id, $width = 480, $height = 320, $responsive = 1)
    {
        self::$instances++;
        $template = $this->app['config']->get(
            'plugins.config.vimeo.template',
            '@plugin/vimeo/templates/vimeo.twig'
        );
        return $this->twig->render($template, [
            'src' => sprintf('//player.vimeo.com/video/%s', $id),
            'width' => $width,
            'height' => $height,
            'responsive' => $responsive,
            'class' => $responsive ? 'video-vimeo-responsive' : '',
            'instances' => self::$instances
        ]);
    }

}
