<?php

use Herbie\DI;
use Herbie\Hook;

class MarkdownPlugin
{

    public static function install()
    {
        $config = DI::get('Config');

        // add twig function / filter
        if ((bool)$config->get('plugins.config.markdown.twig', false)) {
            Hook::attach('twigInitialized', function (\Twig_Environment $twig) {
                $options = ['is_safe' => ['html']];
                $twig->addFunction(
                    new \Twig_SimpleFunction('markdown', ['MarkdownPlugin', 'parseMarkdown'], $options)
                );
                $twig->addFilter(
                    new \Twig_SimpleFilter('markdown', ['MarkdownPlugin', 'parseMarkdown'], $options)
                );
            });
        }

        // add shortcode
        if ((bool)$config->get('plugins.config.markdown.shortcode', true)) {
            Hook::attach('shortcodeInitialized', ['MarkdownPlugin', 'addShortcode']);
        }

        Hook::attach('renderContent', function ($content, array $attributes) {
            if (!in_array($attributes['format'], ['markdown', 'md'])) {
                return $content;
            }
            return MarkdownPlugin::parseMarkdown($content);
        });
    }

    public static function addShortcode(\herbie\sysplugin\shortcode\classes\Shortcode $shortcode)
    {
        $shortcode->add('markdown', ['MarkdownPlugin', 'markdownShortcode']);
    }

    public static function parseMarkdown($value)
    {
        include_once(__DIR__ . '/vendor/Parsedown.php');
        include_once(__DIR__ . '/vendor/ParsedownExtra.php');

        $parser = new \ParsedownExtra();
        $parser->setUrlsLinked(false);
        $html = $parser->text($value);
        return $html;
    }

    public static function markdownShortcode($attribs, $content)
    {
        return self::parseMarkdown($content);
    }
}

MarkdownPlugin::install();
