<?php

use herbie\sysplugin\twig\classes\Twig;

use Herbie\DI;
use Herbie\Hook;

class TwigPlugin
{
    private $twig;

    public function install()
    {
        Hook::attach('pluginsInitialized', [$this, 'initTwig']);
        Hook::attach('renderContent', [$this, 'twigifyContent']);
        Hook::attach('renderLayout', [$this, 'twigifyLayout']);
    }

    public function initTwig()
    {
        $config = DI::get('Config');
        $this->twig = new Twig($config);
        $this->twig->init();
        Di::set('Twig', $this->twig);
        Hook::trigger(Hook::ACTION, 'twigInitialized', $this->twig->getEnvironment());
    }

    public function twigifyContent($content, array $attributes)
    {
        if(empty($attributes['twig'])) {
            return $content;
        }
        return $this->twig->renderString($content);
    }

    public function twigifyLayout($unused, array $attributes)
    {
        return $this->twig->render($attributes['layout']);
    }

}

(new TwigPlugin)->install();
