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
        $attribs = [
            'src' => sprintf('//player.vimeo.com/video/%s', $id),
            'width' => $width,
            'height' => $height,
            'frameborder' => 0
        ];
        $style = '';
        $class = '';
        if(!empty($responsive)) {
            $style = '<style>.video-vimeo-responsive { position: relative; padding-bottom: 56.25%; padding-top: 30px; height: 0; overflow: hidden; max-width: 100%; height: auto; } .video-vimeo-responsive iframe, .video-vimeo-responsive object, .video-vimeo-responsive embed { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }</style>';
            $class = 'video-vimeo-responsive';
        }
        return sprintf(
            '%s<div class="video video-vimeo %s"><iframe %s webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe></div>',
            $style,
            $class,
            $this->buildHtmlAttributes($attribs)
        );
    }

    /**
     * @param array $htmlOptions
     * @return string
     */
    protected function buildHtmlAttributes($htmlOptions = [])
    {
        $attributes = '';
        foreach ($htmlOptions as $key => $value) {
            $attributes .= $key . '="' . $value . '" ';
        }
        return trim($attributes);
    }

}
