<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

use Twig\Error\LoaderError;
use Twig\Loader\LoaderInterface;
use Twig\Source;

class TwigStringLoader implements LoaderInterface
{
    /**
     * @param string $name
     * @return Source
     * @throws LoaderError
     */
    public function getSourceContext($name): Source
    {
        if (true === $this->isLayoutTemplate($name)) {
            throw new LoaderError(sprintf('Template "%s" does not exist.', $name));
        }
        return new Source($name, $name);
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
