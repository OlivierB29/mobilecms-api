<?php

require_once 'utils/SecureRestApi.php';
require_once 'utils/ContentService.php';
/*
 * /api/v1/content/cake?filter=foobar
 */
class CmsApi extends SecureRestApi
{
    const INDEX_JSON = '/index/index.json';
    const REQUESTBODY = 'requestbody';
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


    protected function index()
    {
      $response = new Response();
      $response->setCode(400);
      $response->setMessage('Bad parameters');
      $response->setResult('{}');

      try {
          $this->checkConfiguration();

          $datatype = $this->getDataType();
          $service = new ContentService($this->conf->{'publicdir'});

          //
          // Preflight requests are send by Angular
          //
          if ($this->method === 'OPTIONS') {
              // eg : /api/v1/content
              $response->setResult($this->preflight());
          }

          //
          if (isset($datatype) && strlen($datatype) > 0) {
              // eg : /api/v1/content/calendar
              if ($this->method === 'GET') {
                  if (array_key_exists(0, $this->args)) {
                    //TODO get single index value
                  } else {
                      //
                      //TODO get all records in index
                      //
                  }
              } elseif ($this->method === 'POST') {
                $response = $service->rebuildIndex($datatype, self::ID);

              } elseif ($this->method === 'PUT') {
                  //TODO PUT
              }
          } else {

          }
      } catch (Exception $e) {
          $response->setCode(520);
          $response->setMessage($e->getMessage());
          $response->setResult($this->errorToJson($e->getMessage()));
      } finally {
          /*
           * if ($this->enableHeaders) {
           * header ( "HTTP/1.1 " . $status . " " . $this->_requestStatus ( $status ) );
           * }
           */
          return $response->getResult();
      }
    }

    /**
     * base API path /api/v1/content.
     */
    protected function content()
    {
        $response = new Response();
        $response->setCode(400);
        $response->setMessage('Bad parameters');
        $response->setResult('{}');

        try {
            $this->checkConfiguration();

            $datatype = $this->getDataType();
            $service = new ContentService($this->conf->{'publicdir'});

            //
            // Preflight requests are send by Angular
            //
            if ($this->method === 'OPTIONS') {
                // eg : /api/v1/content
                $response->setResult($this->preflight());
            }

            //
            if (isset($datatype) && strlen($datatype) > 0) {
                // eg : /api/v1/content/calendar
                if ($this->method === 'GET') {
                    if (array_key_exists(0, $this->args)) {
                        //
                        //get the full data of a single record
                        //

                        // $this->args contains the remaining path parameters
                        // eg : /api/v1/content/calendar/1/foo/bar
                        // ['1', 'foo', 'bar']

                        $response = $service->getRecord($datatype, $this->args[0]);
                    } else {
                        //
                        //get all records in index
                        //
                        $response = $service->getAllObjects($datatype);
                    }
                } elseif ($this->method === 'POST') {
                    //
                    // save a record and update the index
                    //
                    // path eg : /api/v1/content/calendar

                    //
                    // step 1 : update Record
                    //
                    $putResponse = $service->post($datatype, self::ID, $this->request[self::REQUESTBODY]);
                    $myobjectJson = json_decode($putResponse->getResult());
                    //TODO manage errors
                    unset($putResponse);

                    //
                    // step 2 : publish to index
                    //
                    $id = $myobjectJson->{self::ID};
                    unset($myobjectJson);
                    $response = $service->publishById($datatype, self::ID, $id);
                } elseif ($this->method === 'PUT') {
                  //
                  // save a record and update the index
                  //
                  // path eg : /api/v1/content/calendar

                  //
                  // step 1 : update Record
                  //
                  $putResponse = $service->post($datatype, self::ID, $this->request);
                  $myobjectJson = json_decode($putResponse->getResult());
                  //TODO manage errors
                  unset($putResponse);

                  //
                  // step 2 : publish to index
                  //
                  $id = $myobjectJson->{self::ID};
                  unset($myobjectJson);
                  $response = $service->publishById($datatype, self::ID, $id);
                }
            } else {
                if ($this->method === 'GET') {
                    //
                    //return the list of editable types
                    //
                    // path eg : /api/v1/content/

                    $response->setResult($service->options('types.json'));
                }
            }
        } catch (Exception $e) {
            $response->setCode(520);
            $response->setMessage($e->getMessage());
            $response->setResult($this->errorToJson($e->getMessage()));
        } finally {
            /*
             * if ($this->enableHeaders) {
             * header ( "HTTP/1.1 " . $status . " " . $this->_requestStatus ( $status ) );
             * }
             */
            return $response->getResult();
        }
    }

    protected function file()
    {
        $this->checkConfiguration();

        $service = new ContentService($this->conf->{'publicdir'});

        // eg : /api/v1/file?filename
        if ($this->method === 'GET') {

            // $this->args contains the remaining path parameters
            // eg : /api/v1/file?file=/calendar/1/foo/bar/sample.json

            if (array_key_exists(self::FILE, $this->getRequest())) {
                // this

                $response = $service->getFilePath($this->getRequest()[self::FILE]);
                if ($response->getCode() === 200) {
                    return file_get_contents($response->getResult());
                } else {
                    return $response->getMessage();
                }
            } else {
                return '';
            }
        } else {
            throw new Exception('bad request');
        }
    }

    private function getDataType(): string
    {
        $datatype = null;
        if (isset($this->verb)) {
            $datatype = $this->verb;
        }
        if (!isset($datatype)) {
            throw new Exception('Empty datatype');
        }

        return $datatype;
    }

    private function checkConfiguration()
    {
        if (!isset($this->conf->{'publicdir'})) {
            throw new Exception('Empty publicdir');
        }
    }

    /**
     * http://stackoverflow.com/questions/25727306/request-header-field-access-control-allow-headers-is-not-allowed-by-access-contr.
     */
    public function preflight(): string
    {
        header('Access-Control-Allow-Methods: *');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

        return '{}';
    }
}
