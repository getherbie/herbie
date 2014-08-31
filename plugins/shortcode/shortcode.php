<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/classes/Shortcode.php';

class ShortcodePlugin extends Herbie\Plugin
{

    public function onContentSegmentLoaded(Herbie\Event $event)
    {
        $tags = $event['app']['config']->get('plugins.shortcode', []);
        $shortcode = new Shortcode($tags);
        $event['segment'] = $shortcode->parse($event['segment']);
    }
}
