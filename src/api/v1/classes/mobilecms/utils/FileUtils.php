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
}
