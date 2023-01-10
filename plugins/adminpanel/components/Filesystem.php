<?php

namespace herbie\sysplugins\adminpanel\components;

use DirectoryIterator;
use herbie\FileInfo;
use SplFileInfo;

class Filesystem
{

    public static function getFiles(string $dir): array
    {
        $files = [];
        $iterator = new DirectoryIterator($dir);
        #$iterator->setInfoClass(FileInfo::class);
        /** @var SplFileInfo $fileInfo */
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }
            $files[] = $fileInfo->getFileInfo();
        }
        return $files;
    }

}
