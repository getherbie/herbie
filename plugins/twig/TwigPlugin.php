<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\sysplugin\twig;

use herbie\sysplugin\twig\classes\Twig;

use Herbie;

class TwigPlugin extends Herbie\Plugin
{
    private $twig;

    public function onPluginsInitialized($event)
    {
        $config = Herbie\DI::get('Config');
        $this->twig = new Twig($config);
        $this->twig->init();
        Herbie\Di::set('Twig', $this->twig);
        Herbie\Application::fireEvent('onTwigInitialized', ['twig' => $this->twig->environment]);
    }

    public function onRenderPageSegment($event)
    {
        $event['content'] = $this->twig->renderPageSegment($event['segment'], $event['page']);
    }

    public function onRenderLayout($event)
    {
        $event['content'] = $this->twig->render($event['layout']);
    }

}
