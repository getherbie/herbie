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

        // Add custom namespace path to Imagine lib
        $vendorDir = $config->get('site.path') . '/../vendor';
        $autoload = require($vendorDir . '/autoload.php');
        $autoload->add('Twig_', __DIR__ . '/vendor/twig/lib');

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

    public function twigifyLayout($page)
    {
        $this->twig->getEnvironment()->getExtension('herbie')->setPage($page);
        return $this->twig->render($page->layout);
    }

}

(new TwigPlugin)->install();
