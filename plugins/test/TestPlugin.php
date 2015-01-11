<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\plugin\test;

use herbie\plugin\test\classes\TestExtension;
use Herbie;

class TestPlugin extends Herbie\Plugin
{

    public function onTwigInitialized(Herbie\Event $event)
    {
        $event['twig']->addExtension(new TestExtension($event['app']));
    }

    public function onPluginsInitialized(Herbie\Event $event)
    {
        // $event['plugins'];
    }

    public function onOutputGenerated(Herbie\Event $event)
    {
        // $event['response'];
    }

    public function onOutputRendered(Herbie\Event $event)
    {
        // no params
    }

    public function onPageLoaded(Herbie\Event $event)
    {
        // $event['page'];
    }

    public function onContentSegmentLoaded(Herbie\Event $event)
    {
        // $event['segment'];
    }

    public function onContentSegmentRendered(Herbie\Event $event)
    {
        // $event['segment'];
    }

}
