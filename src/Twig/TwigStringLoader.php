<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-01-12
 * Time: 16:14
 */

declare(strict_types=1);

namespace Herbie\Twig;

use Twig_Error_Loader;
use Twig_LoaderInterface;
use Twig_Source;

class TwigStringLoader implements Twig_LoaderInterface
{
    /**
     * @param string $name
     * @return Twig_Source
     * @throws Twig_Error_Loader
     */
    public function getSourceContext($name): Twig_Source
    {
        if (true === $this->isLayoutTemplate($name)) {
            throw new Twig_Error_Loader(sprintf('Template "%s" does not exist.', $name));
        }
        return new Twig_Source($name, $name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function exists($name): bool
    {
        $bool = $this->isLayoutTemplate($name);
        return !$bool;
    }

    /**
     * @param string $name
     * @return string
     */
    public function getCacheKey($name): string
    {
        return md5($name);
    }

    /**
     * @param string $name
     * @param int $time
     * @return bool
     */
    public function isFresh($name, $time): bool
    {
        return true;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isLayoutTemplate(string $name): bool
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
