<?php

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
    #[\Override]
    public function getSourceContext(string $name): Source
    {
        if (true === $this->isLayoutTemplate($name)) {
            throw new LoaderError(sprintf('Template "%s" does not exist.', $name));
        }
        return new Source($name, $name);
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

    #[\Override]
    public function exists(string $name): bool
    {
        $bool = $this->isLayoutTemplate($name);
        return !$bool;
    }

    #[\Override]
    public function getCacheKey(string $name): string
    {
        return md5($name);
    }

    #[\Override]
    public function isFresh(string $name, int $time): bool
    {
        return true;
    }
}
