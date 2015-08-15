<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\sysplugin\textile;

use Herbie;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

include_once (__DIR__ . '/vendor/Netcarver/Textile/Parser.php');

class TextilePlugin extends Herbie\Plugin
{
    public function onTwigInitialized($event)
    {
        $options = ['is_safe' => ['html']];
        $twig = $event['twig'];
        $twig->addFunction(
            new Twig_SimpleFunction('textile', [$this, 'parseTextile'], $options)
        );
        $twig->addFilter(
            new Twig_SimpleFilter('textile', [$this, 'parseTextile'], $options)
        );
    }

    public function onShortcodeInitialized($event)
    {
        $event['shortcode']->add('textile', [$this, 'textileShortcode']);
    }

    public function onContentSegmentTwigged($event)
    {
        if(!in_array($event['format'], ['textile'])) {
            return;
        }
        $event['segment'] = $this->parseTextile($event['segment']);
    }

    public function parseTextile($value)
    {
        $parser = new \Netcarver\Textile\Parser();
        return $parser->textileThis($value);
    }

    public function textileShortcode($options, $content)
    {
        return $this->parseTextile($content);
    }

}
