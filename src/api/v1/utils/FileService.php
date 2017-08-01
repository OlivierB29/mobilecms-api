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
}
