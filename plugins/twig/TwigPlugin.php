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

    public function onPluginsInitialized()
    {
        $config = Herbie\DI::get('Config');
        $this->twig = new Twig($config);
        $this->twig->init();
        Herbie\Di::set('Twig', $this->twig);
        Herbie\Application::fireEvent('onTwigInitialized', $this->twig->environment);
    }

    public function onRenderContent($segment, array $attributes)
    {
        if(empty($attributes['twig'])) {
            return;
        }
        $segment->string = $this->twig->renderString($segment->string);
    }

    public function onRenderLayout($content, array $attributes)
    {
        $content->string = $this->twig->render($attributes['layout']);
    }

}
