<?php

namespace herbie\sysplugins\adminpanel\actions\media;

use Herbie\Alias;
use herbie\sysplugins\adminpanel\classes\DirectoryDotFilter;
use herbie\sysplugins\adminpanel\classes\DirectoryIterator;
use Herbie\TwigRenderer;
use Psr\Http\Message\ServerRequestInterface;

class IndexAction
{
    /**
     * @var TwigRenderer
     */
    private $twig;
    /**
     * @var ServerRequestInterface
     */
    private $request;
    /**
     * @var Alias
     */
    private $alias;

    public function __construct(TwigRenderer $twig, ServerRequestInterface $request, Alias $alias)
    {
        $this->twig = $twig;
        $this->request = $request;
        $this->alias = $alias;
    }

    public function __invoke()
    {
        $params = $this->request->getQueryParams();
        $dir = $params['dir'] ?? '';
        $dir = str_replace(['../', '..', './', '.'], '', trim($dir, '/'));
        $path = $this->alias->get('@media/' . $dir);
        $root = $this->alias->get('@media');

        $iterator = null;
        if (is_readable($path)) {
            $directoryIterator = new DirectoryIterator($path, $root);
            $iterator = new DirectoryDotFilter($directoryIterator);
        }

        return $this->twig->renderTemplate('@sysplugin/adminpanel/views/media/index.twig', [
            'iterator' => $iterator,
            'dir' => $dir,
            'parentDir' => str_replace('.', '', dirname($dir)),
            'root' => $root
        ]);
    }
}
