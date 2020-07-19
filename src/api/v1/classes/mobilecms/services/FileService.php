<?php namespace mobilecms\services;

/**
 * File utility service.
 */
class FileService
{
    /**
     * Direct file children from dir.
     *
     * @param string $dir : users folder
     */
    public function getDescriptions(string $dir)
    {
        $result = [];
        $scanned_directory = array_diff(scandir($dir), ['..', '.']);
        foreach ($scanned_directory as $key => $value) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $value;
            if (is_file($filePath)) {
                array_push($result, $this->getFileResponse($filePath, $value));
            }
        }

        return $result;
    }


    /**
     * Get updated file descriptions from a directory.
     *
     * @param string $dir      : home folder
     * @param string $existing : existing descriptions
     */
    public function updateDescriptions($dir, $existing)
    {
        $result = $this->getDescriptions($dir);
        foreach ($result as $key => $value) {
            $url = $value->{'url'};
            $existingFile = \mobilecms\utils\JsonUtils::getByKey($existing, 'url', $url);
            if (isset($existingFile)) {
                $value->{'title'} = $existingFile->{'title'};
            }
        }

        return $result;
    }

    /**
     * Get file info and build JSON response.
     *
     * @param string $destfile : destination file
     * @param string $title    title of file
     */
    public function getFileResponse(string $destfile, string $title)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE); // get mime type
        $mimetype = finfo_file($finfo, $destfile);
        finfo_close($finfo);

        $filesize = filesize($destfile);

        $fileResult = json_decode('{}');
        $fileResult->{'title'} = $title;
        $fileResult->{'url'} = basename($destfile);
        $fileResult->{'size'} = $filesize;
        $fileResult->{'mimetype'} = $mimetype;

        return $fileResult;
    }

    /**
     * Get real path of media files.
     *
     * @param string $mediadir eg: media
     * @param string $type eg: calendar
     * @param string $id       eg: 1
     *
     * @return eg : /var/www/html/media/calendar/1
     */
    public function getRecordDirectory(string $mediadir, string $type, string $id, \stdClass $record): string
    {
        if (isset($mediadir) && isset($type) && isset($id)) {

            $result = $mediadir  . '/' . $type . '/' ;
            // conf "organizeby": "year"
            $conf = $this->getConf($type);
            if (isset($record) && !empty($conf->getString('organizeby'))) {
                // get year from date field
                $recorddate = $record->{$conf->getString('organizefield')};
                $year = substr($recorddate, 0, 4);
                // date should be mandatory
                if (!empty($year)) {
                    $result .=  $year . '/';
                }
                            
            } 
            $result .= $id ;
            return $result; 

        } else {
            // @codeCoverageIgnoreStart
            throw new \Exception('getMediaDirectory() mediadir ' . $mediadir . ' type ' . $type . ' id ' . $id);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @TODO : replace type by a defined subpath such as news/2015
     */
    public function getRecordDirectoryWithoutRecord(string $mediadir, string $type, string $id): string
    {
        if (isset($mediadir) && isset($type) && isset($id)) {

            $result = $mediadir  . '/' . $type . '/' ;


            $result .= $id ;
            return $result; 

        } else {
            // @codeCoverageIgnoreStart
            throw new \Exception('getMediaDirectory() mediadir ' . $mediadir . ' type ' . $type . ' id ' . $id);
            // @codeCoverageIgnoreEnd
        }
    }


    /**
     * Create thumbnails files from specified URLs.
     * @param string $mediadir : destination directory
     * @param string $datatype : news
     * @param string $id       : 123
     * @param string $files : [{ "url": "tennis.jpg", "sizes": [100, 200, 300]}]
     * @param string $defaultsizes : [100, 200, 300, 400, 500]
     *
     * @return \mobilecms\rest\Response result
     */
    public function createThumbnails(
        string $mediadir,
        string $datatype,
        string $id,
        array $files,
        array $defaultsizes,
        int $quality,
        array $defaultPdfsizes,
        int $pdfQuality,
        bool $imagick = false
    ): \mobilecms\rest\Response {
        $response = $this->getDefaultResponse();
        $destdir = $this->getRecordDirectoryWithoutRecord($mediadir, $datatype, $id);


        $result = [];
        $utils = new \mobilecms\utils\ImageUtils();
        $utils->setQuality($quality);
        $utils->setImagick($imagick);
        foreach ($files as $formKey => $file) {
            // /var/www/html/media/calendar/1

            // upload
            if (isset($file->{'url'})) {
                $sizes = null;


                // get foobar.html from http://something.com/[...]/foobar.html
                $filePath = $destdir . '/' . basename($file->{'url'});

                $thumbdir = $destdir . '/thumbnails';
                if (file_exists($filePath)) {
                    // thumbnails sizes
                    if (!empty($file->{'sizes'}) && count($file->{'sizes'}) > 0) {
                        $sizes = $file->{'sizes'};
                    } else {
                        // @codeCoverageIgnoreStart
                        $sizes = $defaultsizes;
                        // @codeCoverageIgnoreEnd
                    }
                    $thumbnails = null;
                    $fileResponse = null;
                    if ($utils->isImage($filePath)) {
                        $thumbnails = $utils->multipleResize($filePath, $thumbdir, $sizes);
                        $fileResponse = $utils->imageInfo($filePath);
                    } else {
                        // thumbnails sizes
                        if (!empty($file->{'sizes'}) && count($file->{'sizes'}) > 0) {
                            $sizes = $file->{'sizes'};
                        } else {
                            // @codeCoverageIgnoreStart
                            $sizes = $defaultPdfsizes;
                            // @codeCoverageIgnoreEnd
                        }
                        // future version with PDF preview : https://gist.github.com/umidjons/11037635
                        $pdfUtils = new \mobilecms\utils\PdfUtils();
                        $fileResponse = $pdfUtils->pdfInfo($filePath);
                        $pdfUtils->setQuality($pdfQuality);
                        $thumbnails = $pdfUtils->multipleResize($filePath, $thumbdir, $sizes);
                    }

                    if (isset($thumbnails)) {
                        $fileResponse->{'thumbnails'} = $thumbnails;
                        \array_push($result, $fileResponse);
                    }
                } else {
                    // TODO add message
                }
            } else {
                throw new \Exception('wrong file ' . $file['url'] . ' KO');
            }
        }

        $response->setResult($result);
        $response->setCode(200);

        return $response;
    }


    /**
     * Initialize a default Response object.
     *
     * @return Response object
     */
    protected function getDefaultResponse() : \mobilecms\rest\Response
    {
        $response = new \mobilecms\rest\Response();
        $response->setCode(400);
        $response->setResult(new \stdClass);

        return $response;
    }
}
