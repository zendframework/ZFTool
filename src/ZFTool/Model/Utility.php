<?php
namespace ZFTool\Model;

use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;

class Utility
{
    /**
     * Copy all files recursively from source to destination
     *
     * @param  string $source
     * @param  string $dest
     * @return boolean
     */
    public static function copyFiles($source, $dest)
    {
        if (!file_exists($source)) {
            return false;
        }
        if (!file_exists($dest)) {
            mkdir($dest);
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $destName = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!file_exists($destName)) {
                    if (!@mkdir($destName)) {
                        return false;
                    }
                }
            } else {
                if (!@copy($item, $destName)) {
                    return false;
                }
                chmod ($destName, fileperms($item));
            }
        }
        return true;
    }

    /**
     * Delete a folder recursively from source to destination
     *
     * @param  string $source
     * @return boolean
     */
    public static function deleteFolder($source)
    {
        if (!file_exists($source)) {
            return false;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()){
                if (!@rmdir($item->getRealPath())) {
                    return false;
                }
            } else {
                if (!@unlink($item->getRealPath())) {
                    return false;
                }
            }
        }
        return @rmdir($source);
    }
}
