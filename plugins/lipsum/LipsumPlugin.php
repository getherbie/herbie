<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\plugin\lipsum;

use herbie\plugin\lipsum\classes\LoremIpsum;

use Herbie;
use Twig_SimpleFunction;

class LipsumPlugin extends Herbie\Plugin
{
    protected $categories = ['abstract', 'animals', 'business', 'cats', 'city', 'food', 'nightlife', 'fashion', 'people', 'nature', 'sports', 'technics', 'transport'];

    /**
     * @param Herbie\Event $event
     */
    public function onTwigInitialized(Herbie\Event $event)
    {
        $event['twig']->addFunction(
            new Twig_SimpleFunction('lipsum_image', [$this, 'image'], ['is_safe' => ['html']])
        );
        $event['twig']->addFunction(
            new Twig_SimpleFunction('lipsum_text', [$this, 'text'], ['is_safe' => ['html']])
        );
        $event['twig']->addFunction(
            new Twig_SimpleFunction('lipsum_title', [$this, 'title'], ['is_safe' => ['html']])
        );
    }

    /**
     * @param int $width
     * @param int $height
     * @param string $category
     * @param string $text
     */
    public function image($width = 200, $height = 200, $category = '', $text = '')
    {
        $src = "http://lorempixel.com/{$width}/{$height}/";
        if (!empty($category) && in_array($category, $this->categories)) {
            $src .= "{$category}/";
        }
        if (!empty($text)) {
            $src .= "{$text}/";
        }
        return sprintf('<img src="%s" width="%d" height="%d" alt="%s">', $src, $width, $height, $text);
    }

    public function title()
    {
        $helper = new LoremIpsum();
        $helper->shuffle();
        return $helper->display('sentences', 1);
    }

    public function text($type)
    {
        $helper = new LoremIpsum();
        $helper->shuffle();
        return $helper->display('sentences', 10) . '.';
    }

}
