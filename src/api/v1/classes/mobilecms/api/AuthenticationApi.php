<?php namespace mobilecms\api;

/*
 * Login API
 * /authapi/v1/auth
 * /authapi/v1/register
 */
class AuthenticationApi extends \mobilecms\utils\RestApi
{
    /**
     * Debug feature.
     */
    private $debugResetPassword = false;

    /**
     * Enable send mail.
     */
    private $enablemail = false;

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

        $this->debugResetPassword = $this->properties->getBoolean('debugnotifications', true);
        $this->enablemail = $this->properties->getBoolean('enablemail', true);
    }



    /**
     * Base API path /authapi/v1/changepassword.
     *
     * @return \mobilecms\utils\Response object
     */
    protected function changepassword() : \mobilecms\utils\Response
    {
        $response = $this->getDefaultResponse();

        //throw error if wrong configuration, such as empty directory
        $this->checkConfiguration();

        $service = new \mobilecms\utils\UserService($this->getPrivateDirPath() . '/users');


        if ($this->requestObject->method === 'POST') {
            // login and get token
            // eg : { "user": "test@example.com", "password":"Sample#123456"}

            $logindata = json_decode($this->getRequestBody());

            //TODO : user contains either email of name

            // free variables before response
            $response = $service->changePassword(
                $this->getUser($logindata),
                $logindata->{'password'},
                $logindata->{'newpassword'}
            );

            unset($logindata);
        }

        return $response;
    }

    /**
     * Base API path /authapi/v1/resetpassword.
     *
     * @return \mobilecms\utils\Response object
     */
    protected function resetpassword() : \mobilecms\utils\Response
    {
        $response = $this->getDefaultResponse();

        //throw error if wrong configuration, such as empty directory
        $this->checkConfiguration();

        $service = new \mobilecms\utils\UserService($this->getPrivateDirPath() . '/users');


        if ($this->requestObject->method === 'POST') {
            // login and get token
            // eg : { "user": "test@example.com", "password":"Sample#123456"}

            $logindata = json_decode($this->getRequestBody());

            //TODO : user contains either email of name

            // free variables before response
            $clearPassword = $service->generateRandomString(20);

            $response = $service->resetPassword($this->getUser($logindata), $clearPassword);

            if ($response->getCode() === 200) {
                $u = new \mobilecms\utils\MailUtils($this->getRootDir());

                $email = $this->getUser($logindata);
                $notificationTitle = 'new password';
                $notificationBody = $u->getNewPassword('new password', $clearPassword, $this->getClientInfo());
                $notificationHeaders = $u->getHeaders($this->getConf()->{'mailsender'});

                if ($this->enablemail) {
                    // @codeCoverageIgnoreStart
                    $CR_Mail = @mail(
                        $email,
                        'new password',
                        $notificationBody,
                        $notificationHeaders
                    );

                    if ($CR_Mail === false) {
                        $response->setError(500, $CR_Mail);
                    } else {
                        $response->setCode(200);
                    }
                    // @codeCoverageIgnoreEnd
                } elseif ($this->debugResetPassword) {
                    $tmpResponse = json_decode($response->getResult());
                    // test only
                    $tmpResponse->{'notification'} = json_encode($notificationBody);
                    $response->setResult(json_encode($tmpResponse));
                }
            }

            unset($logindata);
        }

        return $response;
    }

    /**
     * Base API path /authapi/v1/publicinfo.
     *
     * @return \mobilecms\utils\Response object
     */
    protected function publicinfo() : \mobilecms\utils\Response
    {
        $response = $this->getDefaultResponse();

        //throw error if wrong configuration, such as empty directory
        $this->checkConfiguration();

        $service = new \mobilecms\utils\UserService($this->getPrivateDirPath() . '/users');


        if ($this->requestObject->method === 'GET') {
            $id = '';
            if (isset($this->requestObject->verb)) {
                $id = $this->requestObject->verb;
            }
            $response = $service->getPublicInfo($id);
            unset($user);
        }

        return $response;
    }

    /**
     * /authapi/v1/register.
     *
     * @return \mobilecms\utils\Response object
     */
    protected function register() : \mobilecms\utils\Response
    {
        $response = $this->getDefaultResponse();

        //throw error if wrong configuration, such as empty directory
        $this->checkConfiguration();
        $service = new \mobilecms\utils\UserService($this->getPrivateDirPath() . '/users');


        // register and create a user
        if ($this->requestObject->method === 'POST') {
            $user = json_decode($this->getRequestBody());
            //returns a empty string if success, a string with the message otherwise

            $createresult = $service->createUser(
                $user->{'name'},
                $user->{'email'},
                $user->{'password'},
                'create'
            );
            if ($createresult === null) {
                $response->setCode(200);
                $response->setResult('{}');
            } else {
                $response->setError(400, 'Bad user parameters');
            }
        }

        return $response;
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

        // @codeCoverageIgnoreStart
        if ($this->enableHeaders) {
            header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
        }
        // @codeCoverageIgnoreEnd

        return $response;
    }

    /**
     * Get IP and user agent from client.
     *
     * @return string IP and user agent
     */
    public function getClientInfo(): string
    {
        $result = $this->getClientIp() . ' ';
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            // @codeCoverageIgnoreStart
            $result .= $_SERVER['HTTP_USER_AGENT'];
            // @codeCoverageIgnoreEnd
        }
        return $result;
    }


    /**
     * Base API path /authapi/v1/authenticate.
     *
     * @return \mobilecms\utils\Response object
     */
    protected function authenticate() : \mobilecms\utils\Response
    {
        $response = $this->getDefaultResponse();

        try {
            // error if wrong configuration, such as empty directory
            $this->checkConfiguration();



            if ($this->requestObject->method === 'POST') {
                if (empty($this->getRequestBody())) {
                    throw new \Exception('no login request');
                }
                // login and get token
                // eg : { "user": "test@example.com", "password":"Sample#123456"}
                $logindata = json_decode($this->getRequestBody());

                if (!isset($logindata->{'password'})) {
                    throw new \Exception('no password data');
                }
                $service = new \mobilecms\utils\UserService($this->getPrivateDirPath() . '/users');
                $response = $service->getToken($this->getUser($logindata), $logindata->{'password'});
                unset($logindata);
                // free variables before response
            }
        } catch (\Exception $e) {
            $response->setError(401, $e->getMessage());
            // @codeCoverageIgnoreStart
        } finally {
            // @codeCoverageIgnoreEnd
            return $response;
        }
    }

    private function getUser($logindata): string
    {
        $result = null;
        if (isset($logindata->{'user'})) {
            $result = $logindata->{'user'};
        } else {
            if (isset($logindata->{'email'})) {
                $result = $logindata->{'email'};
            } else {
                // @codeCoverageIgnoreStart
                throw new \Exception('no user data');
                // @codeCoverageIgnoreEnd
            }
        }

        return $result;
    }


    /**
     * Get IP address.
     *
     * @return string IP address
     */
    public function getClientIp(): string
    {
        $ipaddress = '';
        // @codeCoverageIgnoreStart
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        // @codeCoverageIgnoreEnd
        } else {
            $ipaddress = 'UNKNOWN';
        }

        return $ipaddress;
    }

    /**
     * Check if directory is defined.
     */
    private function checkConfiguration()
    {
        if (!isset($this->getConf()->{'privatedir'})) {
            throw new \Exception('Empty privatedir');
        }
    }
}
