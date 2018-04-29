<?php namespace mobilecms\api;

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

    private $thumbnailsizes = [];

    private $pdfthumbnailsizes = [];

    private $pdfimagequality = 80;


    private $fileExtensions = [];

    private $imagequality = 0;

    /**
     * Constructor.
     *

     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Init configuration.
     *
     */
    public function setConf()
    {
        parent::setConf();

        // Default headers for RESTful API
        if ($this->enableHeaders) {
            // @codeCoverageIgnoreStart
            header('Access-Control-Allow-Methods: *');
            // @codeCoverageIgnoreEnd
        }

        $this->media = $this->getConf()->{'media'};
        $this->thumbnailsizes = $this->getConf()->{'thumbnailsizes'};
        // width 214 * height 82
        $this->pdfthumbnailsizes = [100, 200];
        $this->fileExtensions = $this->getConf()->{'fileextensions'};
        $this->quality = $this->properties->getInteger('imagequality', 80);
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


        if ($this->requestObject->match('/fileapi/v1/basicupload/{type}/{id}')) {
            if ($this->requestObject->method === 'GET') {
                // create service
                $service = new \mobilecms\utils\FileService();

                // update files description
                // /var/www/html/media/calendar/1
                $destdir = $this->getRecordDirPath($this->getParam('type'), $this->getParam('id'));

                $uploadResult = $service->getDescriptions($destdir);
                $response->setCode(200);

                $response->setResult(json_encode($uploadResult));
            } elseif ($this->requestObject->method === 'POST') {
                if (array_key_exists(0, $this->requestObject->args)) {
                    //get the full data of a single record
                    // eg : /api/v1/file/calendar/1
                    $uploadResult = $this->uploadFiles($this->getParam('type'), $this->getParam('id'));
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

        if ($this->requestObject->method === 'POST') {
            if ($this->requestObject->match('/fileapi/v1/delete/{type}/{id}')) {
                $deleteResult = $this->deleteFiles(
                    $this->getParam('type'),
                    $this->getParam('id'),
                    urldecode($this->getRequestBody())
                );
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

        if ($this->requestObject->match('/fileapi/v1/download/{type}/{id}')) {
            $service = new \mobilecms\utils\FileService();

            if ($this->requestObject->method === 'POST') {
                $response = $this->downloadFiles(
                    $this->getParam('type'),
                    $this->getParam('id'),
                    urldecode($this->getRequestBody())
                );
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

        if ($this->enableHeaders) {
            // @codeCoverageIgnoreStart
            header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            // @codeCoverageIgnoreEnd
        }

        return $response;
    }

    /**
     * Main storage directory.
     *
     * @return eg : // /var/www/html/media
     */
    public function getMediaDirPath(): string
    {
        return $this->getRootDir() . $this->getConf()->{'media'};
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

        // Basic upload verification
        foreach ($this->files as $formKey => $fileControl) {
            if (!$this->isAllowedExtension($fileControl['name'])) {
                throw new \Exception('forbidden file type');
            }
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
        $result = null;
        $utils = new \mobilecms\utils\ImageUtils();

        if ($utils->isImage($destfile)) {
            $result = $utils->imageInfo($destfile);
        } else {
            $result = \json_decode('{}');
            $fileutils = new \mobilecms\utils\FileUtils();
            $result->{'mimetype'} = $fileutils->getMimeType($destfile);
        }
        $result->{'url'} = $url;
        $result->{'size'} = filesize($destfile);
        $result->{'title'} = $title;

        return $result;
    }

    /**
     * Verify minimal configuration.
     */
    private function checkConfiguration()
    {
        if (!isset($this->getConf()->{'media'})) {
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

    /**
     * Create thumbnails
     *
     * Sample request body :
     * [{ "url": "foobar.jpg", "sizes": [100, 200, 300]}]
     *
     * @return \mobilecms\utils\Response response
     */
    protected function thumbnails(): \mobilecms\utils\Response
    {
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();

        if ($this->requestObject->method === 'POST'
            && $this->requestObject->match('/fileapi/v1/thumbnails/{type}/{id}')) {
            $service = new \mobilecms\utils\FileService();
            $files = json_decode(urldecode($this->getRequestBody()));
            $response = $service->createThumbnails(
                $this->getMediaDirPath(),
                $this->getParam('type'),
                $this->getParam('id'),
                $files,
                $this->thumbnailsizes,
                $this->imagequality,
                $this->pdfthumbnailsizes,
                $this->pdfimagequality
            );
        }

        return $response;
    }

    /**
    * Basic upload verification
    * @param string $file file name
    * @return bool
    */
    private function isAllowedExtension($file): bool
    {
        $result = false;
        if ($file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if (isset($extension)) {
                $result = in_array(strtolower($extension), $this->fileExtensions);
            }
        }
        return $result;
    }
}
