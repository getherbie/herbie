<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-02-10
 * Time: 10:54
 */

namespace herbie\sysplugins\adminpanel\actions;

use Herbie\TwigRenderer;

class IndexAction
{
    /**
     * @var TwigRenderer
     */
    private $twig;

    public function __construct(TwigRenderer $twig)
    {
        $this->twig = $twig;
    }

    public function __invoke()
    {
        return $this->twig->renderTemplate('@sysplugin/adminpanel/views/index.twig');
    }
}
