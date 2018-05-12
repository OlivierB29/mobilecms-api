<?php namespace mobilecms\utils;

/**
 */
class FileUtils
{
    public function getMimeType(string $file)
    {
        $file_info = new \finfo(FILEINFO_MIME_TYPE);
        $mime_type = $file_info->buffer(\file_get_contents($file));
        unset($file_info);
        return $mime_type;
    }


    /**
     * Copy a file and create directory if necessary.
     *
     * @param string $s1 : source
     * @param string $s2 : dest
     */
    // public function mycopy(string $s1, string $s2)
    // {
    //     $path = pathinfo($s2);
    //     if (!file_exists($path['dirname'])) {
    //         mkdir($path['dirname'], 0777, true);
    //     }
    //     if (!copy($s1, $s2)) {
    //         throw new \Exception('copy failed ');
    //     }
    // }

    /**
     * Copy a directory to another
     *
     * @param string $source : source
     * @param string $dest : dest
     */
    public function copydir($source, $dest)
    {
        if (!file_exists($dest)) {
            mkdir($dest, 0755);
        }
        foreach ($iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $item) {
            if ($item->isDir()) {
                if (!file_exists($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName())) {
                    mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
                }
            } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }


    public function deleteDir($dir)
    {
        $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}
