<?php namespace mobilecms\api;

// require_once 'SecureRestApi.php';
// require_once '\mobilecms\utils\FileService.php';
/*
 * File API with authentication.
 * Basic file upload using _FILES
 */
class FileApi extends \mobilecms\utils\SecureRestApi
{
    /**
     * Media directory (eg: media ).
     */
    private $media;

    /**
     * Default umask for directories and files.
     */
    private $umask = 0775;

    private $files;

    private $debug;

    /**
     * Constructor.
     *
     * @param \stdClass $conf JSON configuration
     */
    public function __construct(\stdClass $conf)
    {
        parent::__construct($conf);

        // Default headers for RESTful API
        if ($this->enableHeaders) {
            header('Access-Control-Allow-Methods: *');
        }

        $this->media = $this->conf->{'media'};
    }

    public function setRequest(
        array $REQUEST = null,
        array $SERVER = null,
        array $GET = null,
        array $POST = null,
        array $headers = null,
        array $files = null
    ) {
        parent::setRequest($REQUEST, $SERVER, $GET, $POST, $headers);

        $this->setFiles($files);
    }

    public function setFiles(array $files = null)
    {
        // Useful for tests
        // http://stackoverflow.com/questions/21096537/simulating-http-request-for-unit-testing

        // set reference to avoid objet clone
        if ($files !== null) {
            $this->files = &$files;
        } else {
            $this->files = &$_FILES;
        }
    }

    /**
     * Basic file upload.
     *
     * @return \mobilecms\utils\Response response
     */
    protected function basicupload(): \mobilecms\utils\Response
    {
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();

        $datatype = $this->getDataType();

        //
        // Preflight requests are send by Angular
        //
        if ($this->method === 'OPTIONS') {
            // eg : /api/v1/content
            $response = $this->preflight();
        }

        //
        if (isset($datatype) && strlen($datatype) > 0) {
            // eg : /api/v1/content/calendar
            if ($this->method === 'GET') {
                if (array_key_exists(0, $this->args)) {
                    // object id
                    $id = $this->args[0];
                    // create service
                    $service = new \mobilecms\utils\FileService();

                    // update files description
                    // /var/www/html/media/calendar/1
                    $destdir = $this->getRecordDirPath($datatype, $id);

                    $uploadResult = $service->getDescriptions($destdir);
                    $response->setCode(200);

                    $response->setResult(json_encode($uploadResult));
                }
            } elseif ($this->method === 'POST') {
                if (array_key_exists(0, $this->args)) {
                    //get the full data of a single record $this->args contains the remaining path parameters
                    // eg : /api/v1/file/calendar/1
                    $uploadResult = $this->uploadFiles($datatype, $this->args[0]);
                    $response->setCode(200);

                    $response->setResult(json_encode($uploadResult));
                }
            }
        }

        return $response;
    }

    /**
     * Delete file.
     *
     * @return \mobilecms\utils\Response response
     */
    protected function delete(): \mobilecms\utils\Response
    {
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();

        $datatype = $this->getDataType();

        //
        // Preflight requests are send by Angular
        //
        if ($this->method === 'OPTIONS') {
            // eg : /api/v1/content
            $response = $this->preflight();
        }

        //
        if ($this->method === 'POST') {
            if (array_key_exists(0, $this->args)) {
                $deleteResult = $this->deleteFiles($datatype, $this->args[0], urldecode($this->getRequestBody()));
                $response->setCode(200);

                $response->setResult(json_encode($deleteResult));
            }
        }

        return $response;
    }

    /**
     * Download an external file and save it in the record structure.
     *
     * Sample request body :
     * [{ "url": "http://wwww.example.com/foobar.pdf", "title":"Foobar.pdf"}].
     *
     * @return \mobilecms\utils\Response response
     */
    protected function download(): \mobilecms\utils\Response
    {
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();

        $datatype = $this->getDataType();

        //
        // Preflight requests are send by Angular
        //
        if ($this->method === 'OPTIONS') {
            // eg : /api/v1/content
            $response = $this->preflight();
        }

        //
        if (isset($datatype) && strlen($datatype) > 0) {
            // eg : /api/v1/content/calendar
            if ($this->method === 'GET') {
                // TODO get file
            } elseif ($this->method === 'POST') {
                if (array_key_exists(0, $this->args)) {
                    // $datatype : calendar, $this->args[0] : 1
                    $response = $this->downloadFiles($datatype, $this->args[0], urldecode($this->getRequestBody()));
                }
            }
        }

        return $response;
    }


    /**
     * Preflight response
     * http://stackoverflow.com/questions/25727306/request-header-field-access-control-allow-headers-is-not-allowed-by-access-contr.
     *
     * @return \mobilecms\utils\Response object
     */
    public function preflight(): \mobilecms\utils\Response
    {
        $response = new \mobilecms\utils\Response();
        $response->setCode(200);
        $response->setResult('{}');

        header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

        return $response;
    }

    /**
     * Main storage directory.
     *
     * @return eg : // /var/www/html/media
     */
    public function getMediaDirPath(): string
    {
        return $this->getRootDir() . $this->conf->{'media'};
    }

    /**
     * Record storage directory.
     *
     * @return eg : // /var/www/html/media/calendar/1
     */
    public function getRecordDirPath($type, $id): string
    {
        return $this->getMediaDirPath() . '/' . $type . '/' . $id;
    }

    /**
     * Upload files from $_FILES.
     *
     * @param string $type eg: calendar
     * @param string $id   123
     *
     * @return array of files descriptions
     */
    private function uploadFiles($type, $id): array
    {
        /*
      File properties example
      - name:1.jpg
      - type:image/jpeg
      - tmp_name:/tmp/phpzDc6qT
      - error:0
      - size:700
        */
        $result = json_decode('[]');
        // $_FILES

        if (!isset($this->files) || count($this->files) === 0) {
            throw new \Exception('no file.');
        }

        foreach ($this->files as $formKey => $file) {
            $destdir = $this->getRecordDirPath($type, $id);

            // create directory if it doesn't exist
            if (!file_exists($destdir)) {
                mkdir($destdir, $this->umask, true);
                chmod($destdir, $this->umask);
            }

            // upload
            if (isset($file['tmp_name']) && isset($file['name'])) {
                $destfile = $destdir . '/' . $file['name'];
                $moveResult = false;
                // why not inline notation condition ? a : b;
                // If an exception is thrown with IO, I prefer to be sure of the line in error.
                if (!file_exists($file['tmp_name'])) {
                    throw new \Exception('Uploaded file not found ' . $file['tmp_name']);
                }

                if ($this->debug) {
                    $moveResult = rename($file['tmp_name'], $destfile);
                } else {
                    $moveResult = move_uploaded_file($file['tmp_name'], $destfile);
                }

                if ($moveResult) {
                    chmod($destfile, $this->umask);
                    $title = $file['name'];
                    $url = $file['name'];
                    $fileResult = $this->getFileResponse($destfile, $title, $url);
                    array_push($result, $fileResult);
                } else {
                    throw new \Exception($file['name'] . ' KO');
                }
            }
        }

        if (count($result) === 0) {
            throw new \Exception('no file uploaded. Please check file size.');
        }

        return $result;
    }

    /**
     * Download files from specified URLs.
     *
     * @param string $datatype : news
     * @param string $id       : 123
     * @param string $filesStr : [{ "url": "http://something.com/[...]/foobar.html" }]
     *
     * @return \mobilecms\utils\Response result
     */
    private function downloadFiles(string $datatype, string $id, string $filesStr): \mobilecms\utils\Response
    {
        $response = $this->getDefaultResponse();

        $files = json_decode($filesStr);

        $result = json_decode('[]');
        foreach ($files as $formKey => $file) {
            $destdir = $this->getRecordDirPath($datatype, $id);

            // create directory if it doesn't exist
            if (!file_exists($destdir)) {
                mkdir($destdir, $this->umask, true);
                chmod($destdir, $this->umask);
            }

            // upload
            if (isset($file->{'url'})) {
                $current = file_get_contents($file->{'url'});
                // get foobar.html from http://something.com/[...]/foobar.html
                $destfile = $destdir . '/' . basename($file->{'url'});

                if (file_put_contents($destfile, $current)) {
                    chmod($destfile, $this->umask);
                    $title = $file->{'title'};
                    $url = basename($file->{'url'});
                    $fileResult = $this->getFileResponse($destfile, $title, $url);
                    array_push($result, $fileResult);
                } else {
                    throw new \Exception($file['name'] . ' KO');
                }
            }
        }

        if (count($result) === 0) {
            throw new \Exception('no files');
        }

        $response->setResult(json_encode($result));
        $response->setCode(200);

        return $response;
    }

    /**
     * Get file info and build JSON response.
     *
     * @param string $destfile : file
     * @param string $title    : title of file
     * @param string $url      : url
     */
    private function getFileResponse($destfile, $title, $url): \stdClass
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE); // get mime type
        $mimetype = finfo_file($finfo, $destfile);
        finfo_close($finfo);

        $filesize = filesize($destfile);

        $fileResult = json_decode('{}');
        $fileResult->{'title'} = $title;
        $fileResult->{'url'} = $url;
        $fileResult->{'size'} = $filesize;
        $fileResult->{'mimetype'} = $mimetype;

        return $fileResult;
    }

    /**
     * Get datatype from request.
     *
     * @return string datatype
     */
    private function getDataType(): string
    {
        $datatype = '';
        if (isset($this->verb)) {
            $datatype = $this->verb;
        }
        if (!isset($datatype)) {
            throw new \Exception('Empty datatype');
        }

        return $datatype;
    }

    /**
     * Verify minimal configuration.
     */
    private function checkConfiguration()
    {
        if (!isset($this->conf->{'media'})) {
            throw new \Exception('Empty media dir');
        }
    }

    /**
     * Delete files.
     *
     * @param string $datatype news
     * @param string $id       123
     * @param string $filesStr : [{ "url": "http://something.com/[...]/foobar.html" }]
     */
    private function deleteFiles($datatype, $id, $filesStr): \mobilecms\utils\Response
    {
        $response = $this->getDefaultResponse();

        $files = json_decode($filesStr);

        $result = json_decode('[]');

        foreach ($files as $formKey => $file) {
            // /var/www/html/media/calendar/1
            $destdir = $this->getRecordDirPath($datatype, $id);

            // upload
            if (isset($file->{'url'})) {
                // get foobar.html from http://something.com/[...]/foobar.html
                $destfile = $destdir . '/' . basename($file->{'url'});
                if (file_exists($destfile)) {
                    if (!unlink($destfile)) {
                        throw new \Exception('delete ' . $file['url'] . ' KO');
                    }
                } else {
                    // TODO add message
                }
            } else {
                throw new \Exception('wrong file ' . $file['url'] . ' KO');
            }
        }

        $response->setResult(json_encode($result));
        $response->setCode(200);

        return $response;
    }

    /**
    * enable debug
    * @param boolean value enable debug
    */
    public function setDebug(bool $value)
    {
        $this->debug = $value;
    }
}