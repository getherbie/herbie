<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace herbie\plugin\disqus;

use Herbie;
use Twig_SimpleFunction;

class DisqusPlugin extends Herbie\Plugin
{

    /**
     * @var Twig_Environment
     */
    private $twig;

    public function onTwigInitialized(Herbie\Event $event)
    {
        $this->twig = $event['twig'];
        $this->twig->addFunction(
            new Twig_SimpleFunction('disqus', [$this, 'disqus'], ['is_safe' => ['html']])
        );
    }

    /**
     * @param string $shortname
     * @return string
     */
    public function disqus($shortname)
    {
        $template = $this->app['config']->get(
            'plugins.config.disqus.template',
            '@plugin/disqus/templates/disqus.twig'
        );
        return $this->twig->render($template, [
           'shortname' => $shortname
        ]);
    }

}
