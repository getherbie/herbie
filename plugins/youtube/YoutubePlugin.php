<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\plugin\youtube;

use Herbie;
use Twig_SimpleFunction;

class YoutubePlugin extends Herbie\Plugin
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
            new Twig_SimpleFunction('youtube', [$this, 'youtube'], ['is_safe' => ['html']])
        );
        $this->twig->addFunction(
            new Twig_SimpleFunction('youTube', [$this, 'youtube'], ['is_safe' => ['html']])
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
    public function youtube($id, $width = 480, $height = 320, $responsive = 1)
    {
        self::$instances++;
        $template = $this->app['config']->get(
            'plugins.config.youtube.template',
            '@plugin/youtube/templates/youtube.twig'
        );
        return $this->twig->render($template, [
            'src' => sprintf('//www.youtube.com/embed/%s?rel=0', $id),
            'width' => $width,
            'height' => $height,
            'responsive' => $responsive,
            'class' => $responsive ? 'video-youtube-responsive' : '',
            'instances' => self::$instances
        ]);
    }

}
