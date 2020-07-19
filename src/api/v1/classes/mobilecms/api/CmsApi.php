<?php namespace mobilecms\api;

/*
 * /mobilecmsapi/v1/content/cake?filter=foobar
 */
class CmsApi extends \mobilecms\rest\SecureRestApi
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


    private $service;

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
    public function initConf()
    {
        parent::initConf();

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
     * @return \mobilecms\rest\Response object
     */
    protected function index() : \mobilecms\rest\Response
    {
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();

        if ($this->requestObject->match('/mobilecmsapi/v1/cmsapi/index/{type}')) {
            if ($this->requestObject->method === 'GET') {
                $response = $this->getService()->getAll($this->getParam('type') . '/index/index.json');
            } elseif ($this->requestObject->method === 'POST') {
                $response = $this->getService()->rebuildIndex($this->getParam('type'), self::ID);
            }
        }


        return $response;
    }

    protected function status() : \mobilecms\rest\Response
    {
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();

        //  $pathId = $this->getParam('id');

        if ($this->requestObject->match('/mobilecmsapi/v1/cmsapi/status')) {
            if ($this->requestObject->method === 'GET') {
                $response->setResult(json_decode('{"result":"true"}'));
                $response->setCode(200);
            }
        }

        return $response;
    }

    /**
     * Base API path /mobilecmsapi/v1/content.
     *
     * @return \mobilecms\rest\Response object
     */
    protected function content() : \mobilecms\rest\Response
    {
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();



        //  $pathId = $this->getParam('id');



        if ($this->requestObject->match('/mobilecmsapi/v1/cmsapi/content')) {
            if ($this->requestObject->method === 'GET') {
                //return the list of editable types. eg : /mobilecmsapi/v1/content/

                $response->setResult($this->getService()->options('types.json'));
                $response->setCode(200);
            }
        }
        if ($this->requestObject->match('/mobilecmsapi/v1/cmsapi/content/{type}')) {
            if ($this->requestObject->method === 'GET') {
                $response = $this->getService()->getAllObjects($this->getParam('type'));
            }
            if ($this->requestObject->method === 'POST') {
                // save a record and update the index. eg : /mobilecmsapi/v1/content/calendar
                $this->logger->info('post');


                // step 1 : update Record

                $putResponse = $this->getService()->post($this->getParam('type'), self::ID, json_decode($this->getRequestBody()));
                $myobjectJson = $putResponse->getResult();
                unset($putResponse);

                // step 2 : publish to index
                $id = $myobjectJson->{self::ID};
                unset($myobjectJson);
                
                // issue : sometimes, the index is not refreshed
                $response = $this->getService()->publishById($this->getParam('type'), self::ID, $id);
                // $response = $this->getService()->rebuildIndex($this->getParam('type'), self::ID);
            }
        }

        if ($this->requestObject->match('/mobilecmsapi/v1/cmsapi/content/{type}/{id}')) {
            if ($this->requestObject->method === 'GET') {
                $response = $this->getService()->getRecord($this->getParam('type'), $this->getParam('id'));
            }
            if ($this->requestObject->method === 'DELETE') {
                //delete media
                $fileservice = new \mobilecms\services\FileService();
                $mediadir = $fileservice->getRecordDirectoryWithoutRecord($this->getMediaDirPath(), $this->getParam('type'), $this->getParam('id'));
                unset($fileservice);
                if (\file_exists($mediadir)) {
                    $fileutils = new \mobilecms\utils\FileUtils();
                    $fileutils->deleteDir($mediadir);
                }

                //delete record
                $response = $this->getService()->deleteRecord($this->getParam('type'), $this->getParam('id'));
                // step 1 : update Record

                if ($response->getCode() === 200) {
                    // step 2 : publish to index
                    $response = $this->getService()->rebuildIndex($this->getParam('type'), self::ID);
                }

                // delete a record and update the index. eg : /mobilecmsapi/v1/content/calendar/1.json
            }
        }

        return $response;
    }


    /**
     * Base API path /mobilecmsapi/v1/content.
     *
     * @return \mobilecms\rest\Response object
     */
    protected function deletelist() : \mobilecms\rest\Response
    {
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();

        //  $pathId = $this->getParam('id');


        if ($this->requestObject->match('/mobilecmsapi/v1/cmsapi/deletelist/{type}')) {
            if ($this->requestObject->method === 'POST') {
                // save a record and update the index. eg : /mobilecmsapi/v1/content/calendar


                // step 1 : update Record


                $putResponse = $this->getService()->deleteRecords(
                    $this->getParam('type'),
                    json_decode($this->getRequestBody())
                );
                $myobjectJson = $putResponse->getResult();
                unset($putResponse);
                // step 2 : publish to index
                unset($myobjectJson);
                $response = $this->getService()->rebuildIndex($this->getParam('type'), self::ID);
            }
        }


        return $response;
    }

    /**
     * Get file info.
     *
     * @return \mobilecms\rest\Response object
     */
    protected function metadata() : \mobilecms\rest\Response
    {
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();

        if ($this->requestObject->method === 'GET' && $this->requestObject->match('/mobilecmsapi/v1/cmsapi/metadata/{type}')) {
            $response->setResult(\mobilecms\utils\JsonUtils::readJsonFile($this->getService()->getMetadataFileName($this->getParam('type'))));
            $response->setCode(200);
        } else {
            throw new \Exception('bad request');
        }

        return $response;
    }

    protected function template() : \mobilecms\rest\Response
    {
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();

        if ($this->requestObject->method === 'GET' && $this->requestObject->match('/mobilecmsapi/v1/cmsapi/template/{type}')) {
            $response->setResult(\mobilecms\utils\JsonUtils::readJsonFile($this->getService()->getTemplateFileName($this->getParam('type'))));
            $response->setCode(200);
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
     * @return \mobilecms\rest\Response object
     */
    public function preflight(): \mobilecms\rest\Response
    {
        $response = new \mobilecms\rest\Response();
        $response->setCode(200);
        $response->setResult(new \stdClass);

        if ($this->enableHeaders) {
            // @codeCoverageIgnoreStart
            header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            // @codeCoverageIgnoreEnd
        }

        return $response;
    }

    /**
     * Get a service
     */
    protected function getService(): \mobilecms\services\ContentService
    {
        if ($this->service == null) {
            $this->service = new \mobilecms\services\ContentService($this->getPublicDirPath());
            $this->service->setLogger($this->logger);
        }
        
        return $this->service;
    }
}
