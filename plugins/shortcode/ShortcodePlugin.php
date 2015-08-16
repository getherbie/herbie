<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\sysplugin\shortcode;

use herbie\sysplugin\shortcode\classes\Shortcode;
use Herbie\Config;
use Herbie\Site;

class ShortcodePlugin extends \Herbie\Plugin
{
    protected $shortcode;

    public function __construct(Config $config)
    {
        $tags = $config->get('plugins.config.shortcode', []);
        $this->shortcode = new Shortcode($tags);
        parent::__construct($config);
    }

    public function init()
    {
        $this->addPageTag();
        $this->addSiteTag();
        $this->addSnippetTag();
        $this->addTwigTag();
        // http://getkirby.com/docs/content/text#basic-formats
        $this->addLinkTag();
        $this->addEmailTag();
        $this->addTelTag();
        $this->addImageTag();
        $this->addFileTag();
    }

    public function onContentSegmentLoaded($null, array $attributes)
    {
        $attributes['segment'] = $this->shortcode->parse($attributes['segment']);
    }

    public function getShortcodeObject()
    {
        return $this->shortcode;
    }

    public function add($tag, callable $callable)
    {
        $this->shortcode->add($tag, $callable);
    }

    public function getTags()
    {
        return $this->shortcode->getTags();
    }

    protected function addPageTag()
    {
        $this->add('page', function($options) {
            if (empty($options[0])) {
                return;
            }
            $name = ltrim($options[0], '.');
            $field = \Herbie\DI::get('Page')->{$name};
            if (is_array($field)) {
                $delim = empty($options['join']) ? ' ' : $options['join'];
                return join($delim, $field);
            }
            return $field;
        });
    }

    protected function addSiteTag()
    {
        $this->add('site', function($options) {
            if (empty($options[0])) {
                return;
            }
            $name = ltrim($options[0], '.');
            $site = new Site();
            $field = $site->{$name};
            if (is_array($field)) {
                $delim = empty($options['join']) ? ' ' : $options['join'];
                return join($delim, $field);
            }
            return $field;
        });
    }

    protected function addSnippetTag()
    {
        $this->add('snippet', function($options) {

            $params = $options;

            $options = $this->initOptions([
                'path' => empty($options[0]) ? '' : $options[0]
            ], $options);

            if (empty($options['path'])) {
                return;
            }

            if (isset($params['0'])) {
                unset($params['0']);
            }
            if (isset($params['path'])) {
                unset($params['path']);
            }

            return \Herbie\DI::get('Twig')->render($options['path'], $params);
        });
    }

    protected function addTwigTag()
    {
        $this->add('twig', function($options, $content) {
            return \Herbie\DI::get('Twig')->renderString($content);
        });
    }

    protected function addEmailTag()
    {
        $this->add('email', function($options) {

            $options = $this->initOptions([
                'address' => empty($options[0]) ? '' : $options[0],
                'text' => '',
                'title' => '',
                'class' => ''
            ], $options);

            $attribs = [];
            $attribs['title'] = $options['title'];
            $attribs['class'] = $options['class'];
            $attribs = array_filter($attribs, 'strlen');

            $replace = [
                '{address}' => $options['address'],
                '{attribs}' => $this->buildHtmlAttributes($attribs),
                '{text}' => empty($options['text']) ? $options['address'] : $options['text']
            ];

            return strtr('<a href="mailto:{address}" {attribs}>{text}</a>', $replace);
        });
    }

    protected function addTelTag()
    {
        $this->add('tel', function($options, $content) {
            return '';
        });
    }

    protected function addLinkTag()
    {
        $this->add('link', function($options) {

            $options = $this->initOptions([
                'href' => empty($options[0]) ? '' : $options[0],
                'text' => '',
                'title' => '',
                'class' => '',
                'target' => ''
            ], $options);

            $attribs = [];
            $attribs['title'] = $options['title'];
            $attribs['class'] = $options['class'];
            $attribs['target'] = $options['target'];
            $attribs = array_filter($attribs, 'strlen');

            // Interner Link
            if (strpos($options['href'], 'http') !== 0) {
                $options['href'] = \Herbie\DI::get('Url\UrlGenerator')->generate($options['href']);
            }

            $replace = [
                '{href}' => $options['href'],
                '{attribs}' => $this->buildHtmlAttributes($attribs),
                '{text}' => empty($options['text']) ? $options['href'] : $options['text']
            ];

            return strtr('<a href="{href}" {attribs}>{text}</a>', $replace);
        });
    }

    protected function addImageTag()
    {
        $this->add('image', function ($options) {
            $options = $this->initOptions([
                'src' => empty($options[0]) ? '' : $options[0],
                'width' => '',
                'height' => '',
                'alt' => '',
                'class' => '',
                'link' => '', // @todo
                'caption' => ''
            ], $options);

            $attributes = $this->extractValuesFromArray(['width', 'height', 'alt', 'class'], $options);
            $attributes['alt'] = isset($attributes['alt']) ? $attributes['alt'] : '';

            // Interne Ressource
            if (strpos($options['src'], 'http') !== 0) {
                $options['src'] = $this->config('web.url') . '/' . $options['src'];
            }

            $replace = [
                '{src}' => $options['src'],
                '{attribs}' => $this->buildHtmlAttributes($attributes),
                '{caption}' => empty($options['caption']) ? '' : sprintf('<figcaption>%s</figcaption>', $options['caption'])
            ];
            return strtr('<figure><img src="{src}" {attribs}>{caption}</figure>', $replace);
        });

    }

    protected function addFileTag()
    {
        $this->add('file', function ($options) {
            $options = $this->initOptions([
                'path' => empty($options[0]) ? '' : $options[0],
                'title' => '',
                'text' => '',
                'alt' => '',
                'class' => '',
                'info' => 0
            ], $options);

            $attributes = $this->extractValuesFromArray(['title', 'text', 'alt', 'class'], $options);
            $attributes['alt'] = isset($attributes['alt']) ? $attributes['alt'] : '';

            // Interne Ressource
            if (strpos($options['path'], 'http') !== 0) {
                #$options['path'] = $this->config('web.url') . '/' . $options['src'];
            }

            $info = '';
            if (!empty($options['info'])) {
                $info = $this->getFileInfo($options['path']);
            }

            $replace = [
                '{href}' => $options['path'],
                '{attribs}' => $this->buildHtmlAttributes($attributes),
                '{text}' => empty($options['text']) ? $options['path'] : $options['text'],
                '{info}' => empty($info) ? '' : sprintf('<span class="file-info">%s</span>', $info)
            ];
            return strtr('<a href="{href}" {attribs}>{text}</a>{info}', $replace);
        });

    }

    protected function getFileInfo($path)
    {
        if (!is_readable($path)) {
            return '';
        }
        $replace = [
            '{size}' => $this->human_filesize(filesize($path)),
            '{extension}' => strtoupper(pathinfo($path, PATHINFO_EXTENSION))
        ];
        return strtr(' ({extension}, {size})', $replace);
    }

    protected function human_filesize($bytes, $decimals = 0) {
        $sz = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . $sz[$factor];
    }

    protected function extractValuesFromArray(array $values, array $array)
    {
        $extracted = [];
        foreach ($values as $key) {
            if (array_key_exists($key, $array)) {
                $extracted[$key] = $array[$key];
            }
        }
        return array_filter($extracted, 'strlen');
    }

}
