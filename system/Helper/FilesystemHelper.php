<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Helper;

class FilesystemHelper
{

    /**
     * @param string $filename
     * @return string
     */
    public static function sanitizeFilename($filename)
    {
        $filename = StringHelper::unaccent($filename);
        $filename = mb_strtolower($filename);
        $filename = preg_replace("/[^a-z0-9\. -]/", "", $filename);
        return preg_replace('/( +)|(-+)/', '-', $filename);
    }

    /**
     * @param string $dir
     * @return bool
     */
    public static function rrmdir($dir)
    {
        $dirIterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::CHILD_FIRST);
        if (count($iterator) > 0) {
            $return = true;
            foreach ($iterator as $filename => $fileInfo) {
                if ($fileInfo->isDir()) {
                    $return &= rmdir($filename);
                } else {
                    $return &= unlink($filename);
                }
            }
            return $return;
        }
        return false;
    }

    /**
     * @param string $dir
     * @return int
     */
    public static function rcount($dir)
    {
        $dirIterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($dirIterator);
        return iterator_count($iterator);
    }

    /**
     * @param string $file
     * @return bool
     */
    public static function createBackupFile($file)
    {
        if (!is_file($file)) {
            return false;
        }
        $info = pathinfo($file);
        $backup = sprintf(
            '%s/%s.%s.%s',
            $info['dirname'],
            $info['filename'],
            date('YmdHis'),
            $info['extension']
        );
        return copy($file, $backup);
    }
}
