<?php

use Herbie\DI;
use Herbie\Hook;

class TextilePlugin
{
    /**
     * @return array
     */
    public function install()
    {
        $config = DI::get('Config');
        if ((bool)$config->get('plugins.config.textile.twig', false)) {
            Hook::attach('twigInitialized', [$this, 'addTwigFunctionAndFilter']);
        }
        if ((bool)$config->get('plugins.config.textile.shortcode', true)) {
            Hook::attach('shortcodeInitialized', [$this, 'addSortcode']);
        }
        Hook::attach('renderContent', [$this, 'renderContent']);
    }

    public function addTwigFunctionAndFilter($twig)
    {
        $options = ['is_safe' => ['html']];
        $twig->addFunction(
            new \Twig_SimpleFunction('textile', [$this, 'parseTextile'], $options)
        );
        $twig->addFilter(
            new \Twig_SimpleFilter('textile', [$this, 'parseTextile'], $options)
        );
    }

    public function addSortcode($shortcode)
    {
        $shortcode->add('textile', [$this, 'textileShortcode']);
    }

    public function renderContent($segment, array $attributes)
    {
        if(!in_array($attributes['format'], ['textile'])) {
            return $segment;
        }
        return $this->parseTextile($segment->string);
    }

    public function parseTextile($value)
    {
        include_once (__DIR__ . '/vendor/Netcarver/Textile/Parser.php');

        $parser = new \Netcarver\Textile\Parser();
        return $parser->textileThis($value);
    }

    public function textileShortcode($options, $content)
    {
        return $this->parseTextile($content);
    }

}

(new TextilePlugin())->install();
