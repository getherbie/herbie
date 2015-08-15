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
        $this->addPageAndSiteTag();
        parent::__construct($config);
    }

    public function onContentSegmentLoaded(\Herbie\Event $event)
    {
        $event['segment'] = $this->shortcode->parse($event['segment']);
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

    protected function addPageAndSiteTag()
    {
        $this->add('page', function($options) {
            if (empty($options[0])) {
                return;
            }
            $name = ltrim($options[0], '.');
            $field = \Herbie\DI::create()->get('Page')->{$name};
            if (is_array($field)) {
                $delim = empty($options['join']) ? ' ' : $options['join'];
                return join($delim, $field);
            }
            return $field;
        });

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

}
