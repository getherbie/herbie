<?php

namespace herbie\sysplugins\adminpanel\classes;

class FilesystemHelper
{
    public static function sanitizeRelativePath(string $absolutePath, string $relativeSubPath): string
    {
        $realRootPath = realpath($absolutePath);
        $realSubPath = realpath($realRootPath . '/' . $relativeSubPath);

        if (strpos($realSubPath, $realRootPath) !== 0) {
            throw new \Exception('Invalid path');
        }

        $dir = str_replace($realRootPath, '', $realSubPath);
        return trim($dir, '/');
    }
}