<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class YoutubePlugin extends Herbie\Plugin
{

    /**
     * @var Twig_Environment
     */
    private $twig;

    public function onTwigInitialized(Herbie\Event $event)
    {
        $this->twig = $event['twig'];
        $this->twig->addFunction(
            new Twig_SimpleFunction('youtube', array($this, 'youtube'), ['is_safe' => ['html']])
        );
        $this->twig->addFunction(
            new Twig_SimpleFunction('youTube', array($this, 'youtube'), ['is_safe' => ['html']])
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
        $attribs = array(
            'src' => sprintf('//www.youtube.com/embed/%s?rel=0', $id),
            'width' => $width,
            'height' => $height,
            'frameborder' => 0
        );
        $style = empty($responsive) ? '' : '<style>.video-youtube { position: relative; padding-bottom: 56.25%; padding-top: 30px; height: 0; overflow: hidden; max-width: 100%; height: auto; } .video-youtube iframe, .video-youtube object, .video-youtube embed { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }</style>';
        return sprintf(
            '%s<div class="video video-youtube"><iframe %s allowfullscreen></iframe></div>',
            $style,
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
