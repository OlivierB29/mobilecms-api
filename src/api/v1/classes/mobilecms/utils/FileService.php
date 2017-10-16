<?php namespace mobilecms\utils;

// require_once 'Response.php';
// require_once 'JsonUtils.php';
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
        $result = json_decode('[]');
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
     * Delete file JSON descriptions, if they don't exist.
     *
     * @param string $homedir  : home folder
     * @param string $existing : existing descriptions
     */
    public function cleanDeletedFiles(string $homedir, string $existing)
    {
        $result = json_decode('[]');
        foreach ($existing as $key => $value) {
            $filePath = $homedir . DIRECTORY_SEPARATOR . $value->{'url'};

            if (is_file($filePath)) {
                array_push($result, $value);
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
            $existingFile = JsonUtils::getByKey($existing, 'url', $url);
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
     * @param string $datatype eg: calendar
     * @param string $id       eg: 1
     *
     * @return eg : /var/www/html/media/calendar/1
     */
    public function getRecordDirectory(string $mediadir, string $datatype, string $id): string
    {
        if (isset($mediadir) && isset($datatype) && isset($id)) {
            return $mediadir . '/' . $datatype . '/' . $id;
        } else {
            throw new \Exception('getMediaDirectory() mediadir ' . $mediadir . ' type ' . $datatype . ' id ' . $id);
        }
    }
}