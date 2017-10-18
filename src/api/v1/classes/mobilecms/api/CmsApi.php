<?php namespace mobilecms\api;

// require_once 'SecureRestApi.php';
// require_once '\mobilecms\utils\ContentService.php';
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
     *
     * @param \stdClass $conf JSON configuration
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Init configuration.
     *
     * @param \stdClass $conf JSON configuration
     */
    public function setConf(\stdClass $conf)
    {
        parent::setConf($conf);

        // Default headers for RESTful API
        if ($this->enableHeaders) {
            header('Access-Control-Allow-Methods: *');
            header('Content-Type: application/json');
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

        $datatype = $this->getDataType();

        $service = new \mobilecms\utils\ContentService($this->getPublicDirPath());

        // Preflight requests are send by Angular
        if ($this->requestObject->method === 'OPTIONS') {
            // eg : /api/v1/content
            $response = $this->preflight();
        }

        //
        if (!empty($datatype)) {
            // eg : /api/v1/content/calendar
            if ($this->requestObject->method === 'GET') {
                if (!empty($pathId)) {
                    //TODO get single index value
                } else {
                    $response = $service->getAll($datatype . '/index/index.json');
                }
            } elseif ($this->requestObject->method === 'POST') {
                $response = $service->rebuildIndex($datatype, self::ID);
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

        $datatype = $this->getDataType();

        $pathId = $this->getId();

        $service = new \mobilecms\utils\ContentService($this->getPublicDirPath());

        // Preflight requests are send by Angular
        if ($this->requestObject->method === 'OPTIONS') {
            // eg : /api/v1/content
            $response = $this->preflight();
        }

        if (!empty($datatype)) {
            // eg : /api/v1/content/calendar
            if ($this->requestObject->method === 'GET') {
                if (!empty($pathId)) {
                    //get the full data of a single record

                    // $this->requestObject->args contains the remaining path parameters
                    // eg : /api/v1/content/calendar/1/foo/bar --> ['1', 'foo', 'bar']
                    $response = $service->getRecord($datatype, $pathId);
                } else {
                    //get all records in index
                    $response = $service->getAllObjects($datatype);
                }
            } elseif ($this->requestObject->method === 'POST') {
                // save a record and update the index. eg : /api/v1/content/calendar

                // step 1 : update Record
                $putResponse = $service->post($datatype, self::ID, urldecode($this->getRequestBody()));
                $myobjectJson = json_decode($putResponse->getResult());
                unset($putResponse);

                // step 2 : publish to index
                $id = $myobjectJson->{self::ID};
                unset($myobjectJson);
                $response = $service->publishById($datatype, self::ID, $id);
            } elseif ($this->requestObject->method === 'PUT') {
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
            } elseif ($this->requestObject->method === 'DELETE') {
                if (!empty($pathId)) {
                    //delete a single record

                    // $this->requestObject->args contains the remaining path parameters
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
            if ($this->requestObject->method === 'GET') {
                //return the list of editable types. eg : /api/v1/content/

                $response->setResult($service->options('types.json'));
                $response->setCode(200);
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

        // Preflight requests are send by Angular
        if ($this->requestObject->method === 'OPTIONS') {
            // eg : /api/v1/content
            $response->setCode(200);

            $response = $this->preflight();
        } elseif ($this->requestObject->method === 'GET') {
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
     * Get type from request.
     *
     * @return type
     */
    private function getDataType(): string
    {
        $datatype = '';
        if (isset($this->requestObject->verb)) {
            $datatype = $this->requestObject->verb;
        }
        if (!isset($datatype)) {
            throw new \Exception('Empty datatype');
        }

        return $datatype;
    }

    /**
     * Get id from request.
     *
     * @return id
     */
    private function getId(): string
    {
        $result = '';
        if (isset($this->requestObject->args) && array_key_exists(0, $this->requestObject->args)) {
            $result = $this->requestObject->args[0];
        }

        return $result;
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

        header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

        return $response;
    }
}
