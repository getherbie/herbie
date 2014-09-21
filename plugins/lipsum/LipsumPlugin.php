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

use herbie\plugin\lipsum\classes\Lipsum;

use Herbie;

class LipsumPlugin extends Herbie\Plugin
{

    public function onTwigInitialized(Herbie\Event $event)
    {
        $event['twig']->addGlobal('lipsum', new Lipsum());
    }

}
