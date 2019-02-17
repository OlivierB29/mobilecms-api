<?php namespace mobilecms\utils;

/**
 */
class ImageUtils
{
    /**
    * default image quality
    */
    private $quality = 100;

    private $imagick = false;

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
            // @codeCoverageIgnoreStart
            mkdir($dir, 0777, true);
            // @codeCoverageIgnoreEnd
        }

        foreach ($sizes as $width) {
            // base name : foo-320.pg
            $resizedFileName = $fileName . '-' . (string)$width . '.' . $extension;

            // file name : foobar/foo-320.pg
            $resizedFilePath = $dir . '/' . $resizedFileName;

            $thumbfileResult = $this->resize($file, $resizedFilePath, $width);
            if (!empty($thumbfileResult)) {
                \array_push($result, $thumbfileResult);
            }
        }
        return $result;
    }

    /**
    * @param string $file : file path
    * @return \stdClass true if smaller size is created
    */
    public function imageInfo(string $file)
    {
        $result = \json_decode('{}');
        $fileutils = new \mobilecms\utils\FileUtils();
        $result->{'mimetype'} = $fileutils->getMimeType($file);

        // calculate height
        list($width, $height) = \getimagesize($file);

        $result->{'width'} = (string)$width;
        $result->{'height'} = (string)$height;
        $result->{'url'} = \basename($file);


        return $result;
    }

    /**
    * @param string $fileName : file path
    * @param string $thumbFile : new resized file
    * @param int $width : new resized width
    * @return \stdClass JSON image description {"width":"210","height":"297","url":"document.jpg"}
    */
    public function resize(string $fileName, string $thumbFile, int $width)
    {
        $result = null;
        // detect mime type
        $file_info = new \finfo(FILEINFO_MIME_TYPE);
        $mime_type = $file_info->buffer(\file_get_contents($fileName));
        unset($file_info);

        // calculate height
        list($width_orig, $height_orig) = \getimagesize($fileName);

        if ($width_orig > $width) {
            $ratio_orig = $width_orig/$height_orig;
            $height = intval(round($width/$ratio_orig, 0, PHP_ROUND_HALF_UP));




            // Resample
            $image_p = \imagecreatetruecolor($width, $height);

            if ($mime_type) {
                $result = \json_decode('{}');
                $result->{'width'} = (string)$width;
                $result->{'height'} = (string)$height;
                $result->{'url'} = \basename($thumbFile);

                switch ($mime_type) {
                    case 'image/jpeg':
                        if ($this->imagick) {
                            // https://secure.php.net/manual/en/imagick.thumbnailimage.php
                            // Max vert or horiz resolution
                            $maxsize=$width;

                            // create new Imagick object
                            $image = new \Imagick($fileName);

                            // Resizes to whichever is larger, width or height
                            if ($image->getImageHeight() <= $image->getImageWidth()) {
                                // Resize image using the lanczos resampling algorithm based on width
                                $image->resizeImage($maxsize, 0, \Imagick::FILTER_LANCZOS, 1);
                            } else {
                                // Resize image using the lanczos resampling algorithm based on height
                                $image->resizeImage(0, $maxsize, \Imagick::FILTER_LANCZOS, 1);
                            }

                            // Set to use jpeg compression
                            $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
                            // Set compression level (1 lowest quality, 100 highest quality)
                            $image->setImageCompressionQuality($this->quality);
                            // Strip out unneeded meta data
                            $image->stripImage();
                            // Writes resultant image to output directory
                            $image->writeImage($thumbFile);
                            // Destroys \Imagick object, freeing allocated resources in the process
                            $image->destroy();
                        } else {
                            $image = \imagecreatefromjpeg($fileName);
                            \imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                            \imagejpeg($image_p, $thumbFile, $this->quality);
                        }

                        break;

                    case 'image/png':
                        \imagealphablending($image_p, false);
                        \imagesavealpha($image_p, true);
                        $image = \imagecreatefrompng($fileName);
                        \imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                        \imagepng($image_p, $thumbFile);

                        break;
                    default:
                }
            }
        }
        return $result;
    }

    /**
     * Set quality.
     *
     * @param int $newval set quality
     */
    public function setQuality(int $newval)
    {
        if ($newval > 0) {
            $this->quality = $newval;
        }
    }

    /**
     * Set quality.
     *
     * @param int $newval set quality
     */
    public function setImagick(bool $newval)
    {
        $this->imagick = $newval;
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
}
