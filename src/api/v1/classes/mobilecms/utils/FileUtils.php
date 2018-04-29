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
    /*
    public function mycopy(string $s1, string $s2)
    {
        $path = pathinfo($s2);
        if (!file_exists($path['dirname'])) {
            mkdir($path['dirname'], 0777, true);
        }
        if (!copy($s1, $s2)) {
            throw new \Exception('copy failed ');
        }
    }
    */

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
