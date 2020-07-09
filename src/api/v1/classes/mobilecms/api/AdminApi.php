<?php namespace mobilecms\api;

/**
 * Administration API (users, ...).
 */
class AdminApi extends \mobilecms\rest\SecureRestApi
{
    const INDEX_JSON = '/index/index.json';

    const EMAIL = 'email';

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
        // role control is ensured by parent class
        $this->role = 'admin';
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

        $service = new \mobilecms\services\ContentService($this->getPrivateDirPath());
        $authService = new \mobilecms\services\AuthService($this->getPrivateDirPath() . '/users');


        if ($this->requestObject->match('/mobilecmsapi/v1/adminapi/content/{type}/{id}') && 'users' === $this->getParam('type')) {
            if ($this->requestObject->method === 'GET') {
                $tmpResponse = $service->getRecord($this->getParam('type'), $this->getParam('id'));
                // basic user fields, without password
                if ($tmpResponse->getCode() === 200) {
                    $response->setCode(200);
                    $response->setResult($this->getUserResponse($tmpResponse->getResult()));
                }
            } elseif ($this->requestObject->method === 'POST' && 'users' === $this->getParam('type')) {
                // save a record and update the index. eg : /mobilecmsapi/v1/content/calendar
                // step 1 : update Record

                // update password if needed
                $user = json_decode(urldecode($this->getRequestBody()));
                if (isset($user->{'newpassword'})) {
                    $response = $authService->resetPassword($user->{'email'}, $user->{'newpassword'});
                } else {
                    $putResponse = $service->update(
                        $this->getParam('type'),
                        self::EMAIL,
                        $this->getUserResponse($user)
                    );

                    $myobjectJson = $putResponse->getResult();
                    unset($putResponse);
                    // step 2 : publish to index
                    $id = $myobjectJson->{self::EMAIL};
                    unset($myobjectJson);
                    $response = $service->publishById($this->getParam('type'), self::EMAIL, $id);
                }
            } elseif ($this->requestObject->method === 'PUT') {
            } elseif ($this->requestObject->method === 'DELETE') {
                // delete a single record.
                // eg : /mobilecmsapi/v1/content/calendar/1/foo/bar --> ['1', 'foo', 'bar']
                $response = $service->deleteRecord($this->getParam('type'), $this->getParam('id'));
                if ($response->getCode() === 200) {
                    // rebuild index
                    $response = $service->rebuildIndex($this->getParam('type'), self::EMAIL);
                }

                // delete a record and update the index. eg : /mobilecmsapi/v1/content/calendar/1.json
            }
        }

        if ($this->requestObject->match('/mobilecmsapi/v1/adminapi/content/{type}')) {
            if ($this->requestObject->method === 'GET' && 'users' === $this->getParam('type')) {
                //get all records in directory
                $userService = new \mobilecms\services\UserService($this->getPrivateDirPath() . '/users');
                $response = $userService->getAllUsers();
            }
            if ($this->requestObject->method === 'POST' && 'users' === $this->getParam('type')) {
                // get all properties of a user, unless $user->{'property'} will fail if the request is empty
                $user = $this->getDefaultUser();
                // get parameters from request
                $requestuser = json_decode($this->getRequestBody());

                \mobilecms\utils\JsonUtils::copy($requestuser, $user);

                //returns a empty string if success, a string with the message otherwise

                $createresult = $authService->createUser(
                    $user->{'name'},
                    $user->{'email'},
                    $user->{'password'},
                    'create'
                );
                if (empty($createresult)) {
                    $id = $user->{self::EMAIL};
                    $response = $service->publishById($this->getParam('type'), self::EMAIL, $id);
                    unset($user);
                    $response->setResult(new \stdClass);
                    $response->setCode(200);
                } else {
                    $response->setError(400, $createresult);
                }
            }
        } elseif ($this->requestObject->matchRequest('GET', '/mobilecmsapi/v1/adminapi/content')) {
            //return the list of editable types. eg : /mobilecmsapi/v1/content/
            $response->setResult($service->adminOptions('types.json'));
            $response->setCode(200);
        }

        // set a timestamp response
        //$tempResponse = $response->getResult();
        //$tempResponse->{'timestamp'} = '' . time();
        //$response->setResult($tempResponse);

        return $response;
    }



    /**
     * Basic user fields, without password.
     *
     * @param userStr $userStr JSON user string
     *
     * @return \stdClass JSON user string
     */
    public function getUserResponse(\stdClass $user): \stdClass
    {
        $responseUser = json_decode('{}');
        $responseUser->{'name'} = $user->{'name'};
        $responseUser->{'email'} = $user->{'email'};
        $responseUser->{'role'} = $user->{'role'};

        return $responseUser;
    }



    /**
     * Preflight response.
     *
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
     * Get or refresh index.
     *
     * @return \mobilecms\rest\Response object
     */
    protected function index() : \mobilecms\rest\Response
    {
        $userKey = 'email';
        $response = $this->getDefaultResponse();

        $this->checkConfiguration();

        if ($this->requestObject->match('/mobilecmsapi/v1/adminapi/index/{type}')) {
            $service = new \mobilecms\services\ContentService($this->getPrivateDirPath());

            // eg : /mobilecmsapi/v1/content/calendar
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
        return json_decode('{"name":"", "email":"", "password":"" }');
    }


    /**
     * Check config and throw an exception if needed.
     */
    private function checkConfiguration()
    {
        if (!isset($this->getConf()->{'privatedir'})) {
            // @codeCoverageIgnoreStart
            throw new \Exception('Empty privatedir');
            // @codeCoverageIgnoreEnd
        }
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

        if ($this->requestObject->method === 'GET' && $this->requestObject->match('/mobilecmsapi/v1/adminapi/metadata/{type}')) {
            $service = new \mobilecms\services\ContentService($this->getPrivateDirPath());
            $response->setResult(\mobilecms\utils\JsonUtils::readJsonFile($service->getMetadataFileName($this->getParam('type'))));
            $response->setCode(200);
        } else {
            throw new \Exception('bad request');
        }

        return $response;
    }
}
