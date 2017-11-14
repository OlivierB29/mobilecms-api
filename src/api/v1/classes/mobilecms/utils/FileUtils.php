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



    public function isImage($file)
    {
        $result = false;

        if (!empty($file)) {
            $path_parts = pathinfo($file);

            $extension = $path_parts['extension'];
            if (!empty($extension) && in_array(strtolower($extension), array("jpeg", "jpg", "png", "gif"))) {
                if (exif_imagetype($file) > 0) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    public function isJpeg($file)
    {
        $result = false;

        if (!empty($file)) {
            $path_parts = pathinfo($file);

            $extension = $path_parts['extension'];


            if (!empty($extension) && in_array(strtolower($extension), array("jpeg", "jpg"))) {
                if (exif_imagetype($file) > 0) {
                    $result = true;
                }
            }
        }

        return $result;
    }
}
