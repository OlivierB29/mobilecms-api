<?php

require_once 'SecureRestApi.php';
require_once 'ContentService.php';
/*
 * /api/v1/content/cake?filter=foobar
 */
class CmsApi extends SecureRestApi
{
    const INDEX_JSON = '/index/index.json';

    const ID = 'id';
    const TYPE = 'type';
    const FILE = 'file';

    public function __construct($conf)
    {
        parent::__construct($conf);

        // Default headers for RESTful API
        if ($this->enableHeaders) {
            header('Access-Control-Allow-Methods: *');
            header('Content-Type: application/json');
        }
    }

    /**
    * @return response object
    */
    protected function index() : Response
    {
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();

        $datatype = $this->getDataType();
        $service = new ContentService($this->conf->{'publicdir'});

        // Preflight requests are send by Angular
        if ($this->method === 'OPTIONS') {
            // eg : /api/v1/content
            $response = $this->preflight();
        }

        //
        if (!empty($datatype)) {
            // eg : /api/v1/content/calendar
            if ($this->method === 'GET') {
                if (!empty($pathId)) {
                    //TODO get single index value
                } else {
                    $response = $service->getAll($datatype . '/index/index.json');
                }
            } elseif ($this->method === 'POST') {
                $response = $service->rebuildIndex($datatype, self::ID);
            }
        }

        return $response;
    }

    /**
     * base API path /api/v1/content.
     * @return response object
     */
    protected function content() : Response
    {
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();

        $datatype = $this->getDataType();

        $pathId = $this->getId();

        $service = new ContentService($this->conf->{'publicdir'});

        // Preflight requests are send by Angular
        if ($this->method === 'OPTIONS') {
            // eg : /api/v1/content
            $response = $this->preflight();
        }

        if (!empty($datatype)) {

                // eg : /api/v1/content/calendar
            if ($this->method === 'GET') {
                if (!empty($pathId)) {
                    //get the full data of a single record

                    // $this->args contains the remaining path parameters
                    // eg : /api/v1/content/calendar/1/foo/bar --> ['1', 'foo', 'bar']
                    $response = $service->getRecord($datatype, $pathId);
                } else {
                    //get all records in index
                    $response = $service->getAllObjects($datatype);
                }
            } elseif ($this->method === 'POST') {
                // save a record and update the index. eg : /api/v1/content/calendar

                // step 1 : update Record
                $putResponse = $service->post($datatype, self::ID, urldecode($this->getRequestBody()));
                $myobjectJson = json_decode($putResponse->getResult());
                unset($putResponse);

                // step 2 : publish to index
                $id = $myobjectJson->{self::ID};
                unset($myobjectJson);
                $response = $service->publishById($datatype, self::ID, $id);
            } elseif ($this->method === 'PUT') {
                // save a record and update the index
                // path eg : /api/v1/content/calendar

                // step 1 : update Record
                $putResponse = $service->post($datatype, self::ID, $this->request);
                $myobjectJson = json_decode($putResponse->getResult());
                //TODO manage errors
                unset($putResponse);

                // step 2 : publish to index
                $id = $myobjectJson->{self::ID};
                unset($myobjectJson);
                $response = $service->publishById($datatype, self::ID, $id);
            } elseif ($this->method === 'DELETE') {
                if (!empty($pathId)) {
                    //delete a single record

                    // $this->args contains the remaining path parameters
                    // eg : /api/v1/content/calendar/1/foo/bar --> ['1', 'foo', 'bar']

                    $response = $service->deleteRecord($datatype, $pathId);
                    // step 1 : update Record

                    if ($response->getCode() === 200) {

                                // step 2 : publish to index
                        $response = $service->rebuildIndex($datatype, self::ID);
                    }
                }

                // delete a record and update the index. eg : /api/v1/content/calendar/1.json
            }
        } else {
            if ($this->method === 'GET') {
                //return the list of editable types. eg : /api/v1/content/

                $response->setResult($service->options('types.json'));
                $response->setCode(200);
            }
        }

        return $response;
    }

    /**
    * @return response object
    */
    protected function file() : Response
    {
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();

        $service = new ContentService($this->conf->{'publicdir'});

        // Preflight requests are send by Angular
        if ($this->method === 'OPTIONS') {
            // eg : /api/v1/content
            $response->setCode(200);

            $response = $this->preflight();
        } elseif ($this->method === 'GET') {
            // eg : /api/v1/file?filename
            // $this->args contains the remaining path parameters --> eg : /api/v1/file?file=/calendar/1/foo/bar/sample.json

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
            throw new Exception('bad request');
        }

        return $response;
    }

    private function getDataType(): string
    {
        $datatype = '';
        if (isset($this->verb)) {
            $datatype = $this->verb;
        }
        if (!isset($datatype)) {
            throw new Exception('Empty datatype');
        }

        return $datatype;
    }

    private function getId(): string
    {
        $result = '';
        if (isset($this->args) && array_key_exists(0, $this->args)) {
            $result = $this->args[0];
        }

        return $result;
    }

    private function checkConfiguration()
    {
        if (!isset($this->conf->{'publicdir'})) {
            throw new Exception('Empty publicdir');
        }
    }

    /**
     * http://stackoverflow.com/questions/25727306/request-header-field-access-control-allow-headers-is-not-allowed-by-access-contr.
     * @return response object
     */
    public function preflight(): Response
    {
        $response = new Response();
        $response->setCode(200);
        $response->setResult('{}');

        header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

        return $response;
    }
}
