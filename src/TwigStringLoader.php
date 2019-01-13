<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-01-12
 * Time: 16:14
 */

namespace Herbie;

use Twig_Error_Loader;
use Twig_LoaderInterface;
use Twig_Source;

class TwigStringLoader implements Twig_LoaderInterface
{

    public function __construct()
    {
    }

    public function getSourceContext($name)
    {
        if (true === $this->isLayoutTemplate($name)) {
            throw new Twig_Error_Loader(sprintf('Template "%s" does not exist.', $name));
        }
        return new Twig_Source($name, $name);
    }

    public function exists($name)
    {
        $bool = $this->isLayoutTemplate($name);
        return !$bool;
    }

    public function getCacheKey($name)
    {
        return md5($name);
    }

    public function isFresh($name, $time)
    {
        return true;
    }

    public function isLayoutTemplate($name)
    {
        $pos = strrpos($name, '.');
        if ($pos !== false) {
            $length = strlen($name) - $pos - 1;
            $extension = substr($name, -$length);
            if (in_array($extension, ['twig', 'html'])) {
                return true;
            }
        }
        return false;
    }

}
