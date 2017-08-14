<?php

require_once 'SecureRestApi.php';
require_once 'ContentService.php';
require_once 'UserService.php';
/*
 * /api/v1/content/cake?filter=foobar
 */
class AdminApi extends SecureRestApi
{
    const INDEX_JSON = '/index/index.json';
    const REQUESTBODY = 'requestbody';

    public function __construct($conf)
    {
        parent::__construct($conf);
        // Default headers for RESTful API
        if ($this->enableHeaders) {
            header('Access-Control-Allow-Methods: *');
            header('Content-Type: application/json');
        }
    }

    protected function index() : Response
    {
      $userKey = 'email';
      $response = $this->getDefaultResponse();

        try {
            $this->checkConfiguration();

            $datatype = $this->getDataType();
            $service = new ContentService($this->conf->{'privatedir'});

          // Preflight requests are send by Angular
          if ($this->method === 'OPTIONS') {
              // eg : /api/v1/content
              $response = $this->preflight();
          }

          if (!empty($datatype)) {
              // eg : /api/v1/content/calendar
              if ($this->method === 'GET') {
                  if (!empty($pathId)) {
                      //TODO get single index value
                  } else {
                      $response = $service->getAll($datatype.'/index/index.json');
                  }
              } elseif ($this->method === 'POST') {
                  $response = $service->rebuildIndex($datatype, $userKey);
              }
          }
        } catch (Exception $e) {
            $response->setError(500, $e->getMessage());
        } finally {

          return $response;
        }
    }

    /**
     * base API path /api/v1/user.
     */
    protected function users() : Response
    {
        $userKey = 'email';

        $response = $this->getDefaultResponse();

        try {
            $this->checkConfiguration();

            $id = $this->getDataType();
            $datatype = 'users';
            $service = new ContentService($this->conf->{'privatedir'});

            // Preflight requests are send by Angular
            if ($this->method === 'OPTIONS') {
                // eg : /api/v1/content
                $response = $this->preflight();
            }

                // eg : /api/v1/content/calendar
                if ($this->method === 'GET') {

                    if (!empty($id)) {
                        //get the full data of a single record. $this->args contains the remaining path parameters  eg : /api/v1/content/calendar/1/foo/bar --> ['1', 'foo', 'bar']
                        $response = $service->getRecord($datatype, $id);

                        // basic user fields, without password
                        $response->setResult($this->getUserResponse($response->getResult()));

                    } else {
                        //get all records in index
                        $response = $service->getAllObjects($datatype);
                    }
                } elseif ($this->method === 'POST') {

                } elseif ($this->method === 'PUT') {

                } elseif ($this->method === 'DELETE') {
                    if (array_key_exists(0, $this->args)) {
                        $response = $service->deleteRecord($datatype, $id);
                            if ($response->getCode() === 200) {
                              $response = $service->rebuildIndex($datatype, $userKey);
                            }
                    }

                  // delete a record and update the index. eg : /api/v1/content/calendar/1.json
                }

        } catch (Exception $e) {
            $response->setError(500, $e->getMessage());
        } finally {
            return $response;
        }
    }

    /**
    * basic user fields, without password
    * @return JSON user string
    */
    public function getUserResponse($userStr) {
      $completeUserObj = json_decode($userStr);
      $responseUser = json_decode('{}');
      $responseUser->{'name'} = $completeUserObj->{'email'};
      $responseUser->{'email'} = $completeUserObj->{'email'};
      $responseUser->{'role'} = $completeUserObj->{'role'};
      return json_encode($responseUser);
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
