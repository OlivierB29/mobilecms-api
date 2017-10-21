<?php namespace mobilecms\utils;

/**
 */
class ImageUtils
{


  /**
  *
  * @return bool true if smaller size is created
  */
    public function resize($fileName, $thumbFile, $width)
    {
        $result = false;
        $file_info = new \finfo(FILEINFO_MIME_TYPE);
        $mime_type = $file_info->buffer(\file_get_contents($fileName));
        unset($file_info);

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



    public function createThumbnailImage($file, $thumbFile, $thumbWidth)
    {
        $name = basename($file);
        $dir = dirname($file);


        $thumbDir = dirname($thumbFile);

        $result = null;
        if ($this->isJpeg($file)) {
            if (!file_exists($thumbFile) || true) {
                if (!file_exists($thumbDir)) {
                    if (!mkdir($thumbDir, 0777, true)) {
                        throw new \Exception('Cannot create directory' . $thumbDir);
                    }
                }

                // load image and get image size
                $img = imagecreatefromjpeg($file);
                $width = imagesx($img);
                $height = imagesy($img);

                // calculate thumbnail size
                $new_width = $thumbWidth;
                $new_height = floor($height * ($thumbWidth / $width));

                // create a new temporary image
                $tmp_img = imagecreatetruecolor($new_width, $new_height);

                // copy and resize old image into new image
                //imagecopyresized() : bad quality
                imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

                // save thumbnail into a file
                imagejpeg($tmp_img, $thumbFile, 100);
            }

            $result = $thumbFile;
        }



        return $result ;
    }
}
