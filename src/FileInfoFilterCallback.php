<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

final class FileInfoFilterCallback
{
    private array $extensions;

    public function __construct(array $extensions)
    {
        $this->extensions = $extensions;
    }

    public function __invoke(\SplFileInfo $file): bool
    {
        $firstChar = substr($file->getFilename(), 0, 1);
        if (in_array($firstChar, ['.', '_'])) {
            return false;
        }

        if ($file->isDir()) {
            return true;
        }

        if (!in_array($file->getExtension(), $this->extensions)) {
            return false;
        }

        return true;
    }
}
