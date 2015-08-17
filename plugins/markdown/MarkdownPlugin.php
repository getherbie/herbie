<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\sysplugin\markdown;

use Herbie;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

include_once (__DIR__ . '/vendor/Parsedown.php');
include_once (__DIR__ . '/vendor/ParsedownExtra.php');

class MarkdownPlugin extends Herbie\Plugin
{

    public function onTwigInitialized($twig)
    {
        $options = ['is_safe' => ['html']];
        $twig->addFunction(
            new Twig_SimpleFunction('markdown', [$this, 'parseMarkdown'], $options)
        );
        $twig->addFilter(
            new Twig_SimpleFilter('markdown', [$this, 'parseMarkdown'], $options)
        );
    }

    public function onShortcodeInitialized($shortcode)
    {
        $shortcode->add('markdown', [$this, 'markdownShortcode']);
    }

    public function onRenderContent($segment, array $attributes)
    {
        if(!in_array($attributes['format'], ['markdown', 'md'])) {
            return;
        }
        $segment->string = $this->parseMarkdown($segment->string);
    }

    public function parseMarkdown($value)
    {
        $parser = new \ParsedownExtra();
        $parser->setUrlsLinked(false);
        return $parser->text($value);
    }

    public function markdownShortcode($options, $content)
    {
        return $this->parseMarkdown($content);
    }

}
