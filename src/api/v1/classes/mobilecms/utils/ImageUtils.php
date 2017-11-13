<?php namespace mobilecms\utils;

/**
 */
class ImageUtils
{

  /**
  * Create a list of thumbnails
  * @param string $fileName : file path
  * @param string $dir : directory containing resized files
  * @param array $sizes : array of new resized widths
  * @return array created files
  */
    public function multipleResize(string $file, string $dir, array $sizes)
    {
        $result = [];

        $fileName = pathinfo($file, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        // create directory if necessary
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        foreach ($sizes as $width) {
            // base name : foo-320.pg
            $resizedFileName = $fileName . '-' . (string)$width . '.' . $extension;

            // file name : foobar/foo-320.pg
            $resizedFilePath = $dir . '/' . $resizedFileName;


            if ($this->resize($file, $resizedFilePath, $width)) {
                $thumbfileResult = \json_decode('{}');
                $thumbfileResult->{'url'} = \basename($resizedFilePath);
                $thumbfileResult->{'width'} = $width;
                \array_push($result, $thumbfileResult);
            }
        }
        return $result;
    }



    /**
    * @param string $fileName : file path
    * @param string $thumbFile : new resized file
    * @param int $width : new resized width
    * @return bool true if smaller size is created
    */
    public function resize(string $fileName, string $thumbFile, int $width)
    {
        $result = false;
        // detect mime type
        $file_info = new \finfo(FILEINFO_MIME_TYPE);
        $mime_type = $file_info->buffer(\file_get_contents($fileName));
        unset($file_info);

        // calculate height
        list($width_orig, $height_orig) = \getimagesize($fileName);

        if ($width_orig > $width) {
            $ratio_orig = $width_orig/$height_orig;
            $height = $width/$ratio_orig;

            // Resample
            $image_p = \imagecreatetruecolor($width, $height);

            if ($mime_type) {
                switch ($mime_type) {
                    case 'image/jpeg':
                        $image = \imagecreatefromjpeg($fileName);
                        \imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                        \imagejpeg($image_p, $thumbFile, 100);
                        $result = true;
                        break;

                    case 'image/png':
                        \imagealphablending($image_p, false);
                        \imagesavealpha($image_p, true);
                        $image = \imagecreatefrompng($fileName);
                        \imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                        \imagepng($image_p, $thumbFile);
                        $result = true;
                        break;
                }
            }
        }
        return $result;
    }

    // ---------------------------------------------------------

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
