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

final class TwigStringLoader implements LoaderInterface
{
    /**
     * @throws LoaderError
     */
    public function getSourceContext(string $name): Source
    {
        if (true === $this->isLayoutTemplate($name)) {
            throw new LoaderError(sprintf('Template "%s" does not exist.', $name));
        }
        return new Source($name, $name);
    }

    public function exists(string $name): bool
    {
        $bool = $this->isLayoutTemplate($name);
        return !$bool;
    }

    public function getCacheKey(string $name): string
    {
        return md5($name);
    }

    public function isFresh(string $name, int $time): bool
    {
        return true;
    }

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
