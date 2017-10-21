<?php namespace mobilecms\api;

/**
 * Administration API (users, ...).
 */
class AdminApi extends \mobilecms\utils\SecureRestApi
{
    const INDEX_JSON = '/index/index.json';

    const EMAIL = 'email';

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
        $this->role = 'admin';
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

        $service = new \mobilecms\utils\ContentService($this->getPrivateDirPath());

        // Preflight requests are send by Angular
        if ($this->requestObject->method === 'OPTIONS') {
            // eg : /api/v1/content
            $response = $this->preflight();
        }


        if ($this->requestObject->match('/adminapi/v1/content/{type}/{id}')) {
            if ($this->requestObject->method === 'GET') {
                $tmpResponse = $service->getRecord($this->getParam('type'), $this->getParam('id'));
                // basic user fields, without password
                if ($tmpResponse->getCode() === 200) {
                    // get the full data of a single record.
                    $response->setCode(200);
                    $response->setResult($this->getUserResponse($tmpResponse->getResult()));
                }
            } elseif ($this->requestObject->method === 'POST') {
                $userService = new \mobilecms\utils\UserService($this->getPrivateDirPath() . '/users');

                // save a record and update the index. eg : /api/v1/content/calendar
                // step 1 : update Record

                // update password if needed
                $userParam = urldecode($this->getRequestBody());
                $user = json_decode($userParam);
                if (isset($user->{'newpassword'})) {
                    $response = $userService->changePasswordByAdmin($user->{'email'}, $user->{'newpassword'});
                }

                $putResponse = $service->update($this->getParam('type'), self::EMAIL, $this->getUserResponse($userParam));

                $myobjectJson = json_decode($putResponse->getResult());
                unset($putResponse);

                // step 2 : publish to index
                $id = $myobjectJson->{self::EMAIL};
                unset($myobjectJson);
                $response = $service->publishById($this->getParam('type'), self::EMAIL, $id);
            } elseif ($this->requestObject->method === 'PUT') {
            } elseif ($this->requestObject->method === 'DELETE') {
                // delete a single record.
                // eg : /api/v1/content/calendar/1/foo/bar --> ['1', 'foo', 'bar']
                $response = $service->deleteRecord($this->getParam('type'), $this->getParam('id'));
                if ($response->getCode() === 200) {
                    // rebuild index
                    $response = $service->rebuildIndex($this->getParam('type'), self::EMAIL);
                }

                // delete a record and update the index. eg : /api/v1/content/calendar/1.json
            }
        }

        if ($this->requestObject->match('/adminapi/v1/content/{type}')) {
            if ($this->requestObject->method === 'GET') {
                //get all records in index
                $response = $service->getAllObjects($this->getParam('type'));
            }
            if ($this->requestObject->method === 'POST') {

              // get all properties of a user, unless $user->{'property'} will fail if the request is empty
                $user = $this->getDefaultUser();
                // get parameters from request
                $requestuser = json_decode($this->getRequestBody());

                JsonUtils::copy($requestuser, $user);

                //returns a empty string if success, a string with the message otherwise

                $createresult = $userService->createUserWithSecret(
              $user->{'name'},
              $user->{'email'},
              $user->{'password'},
              $user->{'secretQuestion'},
              $user->{'secretResponse'},
              'create'
              );
                if (empty($createresult)) {
                    $id = $user->{self::EMAIL};
                    $response = $service->publishById($this->getParam('type'), self::EMAIL, $id);
                    unset($user);
                    $response->setResult('{}');
                    $response->setCode(200);
                } else {
                    $response->setError(400, $createresult);
                }
            }
        }


        if ($this->requestObject->matchRequest('GET', '/adminapi/v1/content')) {
            //return the list of editable types. eg : /api/v1/content/
            $response->setResult($service->options('types.json'));
            $response->setCode(200);
        }
        // set a timestamp response
        $tempResponse = json_decode($response->getResult());
        $tempResponse->{'timestamp'} = '' . time();
        $response->setResult(json_encode($tempResponse));

        return $response;
    }



    /**
     * Basic user fields, without password.
     *
     * @param userStr $userStr JSON user string
     *
     * @return \stdClass JSON user string
     */
    public function getUserResponse($userStr): string
    {
        $completeUserObj = json_decode($userStr);
        $responseUser = json_decode('{}');
        $responseUser->{'name'} = $completeUserObj->{'name'};
        $responseUser->{'email'} = $completeUserObj->{'email'};
        $responseUser->{'role'} = $completeUserObj->{'role'};

        return json_encode($responseUser);
    }



    /**
     * Preflight response.
     *
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
     * Get or refresh index.
     *
     * @return \mobilecms\utils\Response object
     */
    protected function index() : \mobilecms\utils\Response
    {
        $userKey = 'email';
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();

        // Preflight requests are send by Angular
        if ($this->requestObject->method === 'OPTIONS') {
            // eg : /api/v1/content
            $response = $this->preflight();
        } elseif ($this->requestObject->match('/cmsapi/v1/index/{type}')) {
            $service = new \mobilecms\utils\ContentService($this->getPrivateDirPath());

            // eg : /api/v1/content/calendar
            if ($this->requestObject->method === 'GET') {
                $response = $service->getAll($this->getParam('type') . '/index/index.json');
            } elseif ($this->requestObject->method === 'POST') {
                $response = $service->rebuildIndex($this->getParam('type'), $userKey);
            }
        }

        return $response;
    }

    /**
     * Initialize a default user object.
     *
     * *@return \stdClass user JSON object
     */
    private function getDefaultUser(): \stdClass
    {
        return json_decode('{"name":"", "email":"", "password":"", "secretQuestion":"", "secretResponse":"" }');
    }


    /**
     * Check config and throw an exception if needed.
     */
    private function checkConfiguration()
    {
        if (!isset($this->getConf()->{'privatedir'})) {
            throw new \Exception('Empty publicdir');
        }
    }
}
