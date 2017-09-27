<?php

require_once 'Response.php';
require_once 'JsonUtils.php';
/**
 * file utility service.
 */
class FileService
{
    /**
     * Direct file children from dir.
     *
     * @param $dir : users folder
     */
    public function getDescriptions($dir)
    {
        $result = json_decode('[]');
        $scanned_directory = array_diff(scandir($dir), ['..', '.']);
        foreach ($scanned_directory as $key => $value) {
            $filePath = $dir.DIRECTORY_SEPARATOR.$value;
            if (is_file($filePath)) {
                array_push($result, $this->getFileResponse($filePath, $value));
            }
        }

        return $result;
    }

    /**
     * delete file JSON descriptions, if they don't exist.
     *
     * @param $homedir : home folder
     * @param $existing : existing descriptions
     */
    public function cleanDeletedFiles($homedir, $existing)
    {
        $result = json_decode('[]');
        foreach ($existing as $key => $value) {
            $filePath = $homedir.DIRECTORY_SEPARATOR.$value->{'url'};

            if (is_file($filePath)) {
                array_push($result, $value);
            }
        }

        return $result;
    }

    /**
     * get updated file descriptions from a directory.
     *
     * @param $dir : home folder
     * @param $existing : existing descriptions
     */
    public function updateDescriptions($dir, $existing)
    {
        $result = $this->getDescriptions($dir);
        foreach ($result as $key => $value) {
            $url = $value->{'url'};
            $existingFile = JsonUtils::getByKey($existing, 'url', $url);
            if (isset($existingFile)) {
                $value->{'title'} = $existingFile->{'title'};
            }
        }

        return $result;
    }

    /**
     * get file info and build JSON response.
     *
     * @param $destfile : destination file
     * @param $title title
     */
    public function getFileResponse($destfile, $title)
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
     * get real path of media files.
     *
     * @param $mediadir eg: media
     * @param $datatype eg: calendar
     * @param $id eg: 1
     *
     * @return eg : /var/www/html/media/calendar/1
     */
    public function getRecordDirectory($mediadir, $datatype, $id): string
    {
        if (isset($mediadir) && isset($datatype) && isset($id)) {
            return $mediadir.'/'.$datatype.'/'.$id;
        } else {
            throw new Exception('getMediaDirectory mediadir:'.$mediadir.' type:'.$datatype.' id:'.$id);
        }
    }
}
