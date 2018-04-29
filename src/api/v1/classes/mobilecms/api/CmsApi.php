<?php namespace mobilecms\api;

/*
 * /api/v1/content/cake?filter=foobar
 */
class CmsApi extends \mobilecms\utils\SecureRestApi
{
    /**
     * Index subpath
     * full path, eg : /var/www/html/public/calendar/index/index.json.
     */
    const INDEX_JSON = '/index/index.json';

    /*
    * reserved id column
    */
    const ID = 'id';

    /*
    */
    const FILE = 'file';

    /**
     * Constructor.
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
            header('Content-Type: application/json');
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Get index.
     *
     * @return \mobilecms\utils\Response object
     */
    protected function index() : \mobilecms\utils\Response
    {
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();



        $service = new \mobilecms\utils\ContentService($this->getPublicDirPath());

        if ($this->requestObject->match('/cmsapi/v1/index/{type}')) {
            //  $response = $service->getAllObjects($this->getParam('type'));

            if ($this->requestObject->method === 'GET') {
                $response = $service->getAll($this->getParam('type') . '/index/index.json');
            } elseif ($this->requestObject->method === 'POST') {
                $response = $service->rebuildIndex($this->getParam('type'), self::ID);
            }
        }


        return $response;
    }

    /**
     * Base API path /api/v1/content.
     *
     * @return \mobilecms\utils\Response object
     */
    protected function content() : \mobilecms\utils\Response
    {
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();



        //  $pathId = $this->getParam('id');

        $service = new \mobilecms\utils\ContentService($this->getPublicDirPath());

        if ($this->requestObject->match('/cmsapi/v1/content')) {
            if ($this->requestObject->method === 'GET') {
                //return the list of editable types. eg : /api/v1/content/

                $response->setResult($service->options('types.json'));
                $response->setCode(200);
            }
        }
        if ($this->requestObject->match('/cmsapi/v1/content/{type}')) {
            if ($this->requestObject->method === 'GET') {
                $response = $service->getAllObjects($this->getParam('type'));
            }
            if ($this->requestObject->method === 'POST') {
                // save a record and update the index. eg : /api/v1/content/calendar


                //  $response = $service->getAllObjects($this->getParam('type'));
                // step 1 : update Record
                $putResponse = $service->post($this->getParam('type'), self::ID, urldecode($this->getRequestBody()));
                $myobjectJson = json_decode($putResponse->getResult());
                unset($putResponse);

                // step 2 : publish to index
                $id = $myobjectJson->{self::ID};
                unset($myobjectJson);
                $response = $service->publishById($this->getParam('type'), self::ID, $id);
            }
        }

        if ($this->requestObject->match('/cmsapi/v1/content/{type}/{id}')) {
            if ($this->requestObject->method === 'GET') {
                $response = $service->getRecord($this->getParam('type'), $this->getParam('id'));
            }
            if ($this->requestObject->method === 'DELETE') {
                //delete media
                $fileservice = new \mobilecms\utils\FileService();
                $mediadir = $fileservice->getRecordDirectory($this->getMediaDirPath(), $this->getParam('type'), $this->getParam('id'));
                unset($fileservice);
                if (\file_exists($mediadir)) {
                    $fileutils = new \mobilecms\utils\FileUtils();
                    $fileutils->deleteDir($mediadir);
                }

                //delete record
                $response = $service->deleteRecord($this->getParam('type'), $this->getParam('id'));
                // step 1 : update Record

                if ($response->getCode() === 200) {
                    // step 2 : publish to index
                    $response = $service->rebuildIndex($this->getParam('type'), self::ID);
                }

                // delete a record and update the index. eg : /api/v1/content/calendar/1.json
            }
        }

        return $response;
    }


    /**
     * Get file info.
     *
     * @return \mobilecms\utils\Response object
     */
    protected function file() : \mobilecms\utils\Response
    {
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();

        $service = new \mobilecms\utils\ContentService($this->getPublicDirPath());

        if ($this->requestObject->method === 'GET') {
            // eg : /api/v1/file?filename
            // args contains the remaining path parameters
            // --> eg : /api/v1/file?file=/calendar/1/foo/bar/sample.json

            if (array_key_exists(self::FILE, $this->getRequest())) {
                // this

                $filePathResponse = $service->getFilePath($this->getRequest()[self::FILE]);
                if ($filePathResponse->getCode() === 200) {
                    $response->setResult(file_get_contents($filePathResponse->getResult()));
                    $response->setCode(200);
                } else {
                    $response = $filePathResponse;
                }
            }
        } else {
            throw new \Exception('bad request');
        }

        return $response;
    }




    /**
     * Ensure minimal configuration values.
     */
    private function checkConfiguration()
    {
        if (!isset($this->getConf()->{'publicdir'})) {
            throw new \Exception('Empty publicdir');
        }
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
}
