<?php namespace mobilecms\api;

// require_once 'RestApi.php';
// require_once '\mobilecms\utils\UserService.php';
// require_once 'MailUtils.php';

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

        // Default value is true
        if (!empty($this->getConf()->{'debugnotifications'}) && 'true' === $this->getConf()->{'debugnotifications'}) {
            $this->debugResetPassword = true;
        }

        if (!empty($this->getConf()->{'enablemail'}) && 'true' === $this->getConf()->{'enablemail'}) {
            $this->enablemail = true;
        }
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

        // Preflight requests are send by Angular
        if ($this->requestObject->method === 'OPTIONS') {
            // eg : /authapi/v1/auth
            $response->setResult($service->preflight());
        }

        if ($this->requestObject->method === 'POST') {
            // login and get token
            // eg : { "user": "test@example.com", "password":"Sample#123456"}

            $logindata = json_decode($this->getRequestBody());

            //TODO : user contains either email of name

            // free variables before response
            $response = $service->changePassword(
                $logindata->{'user'},
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

        // Preflight requests are send by Angular
        if ($this->requestObject->method === 'OPTIONS') {
            // eg : /authapi/v1/auth
            $response->setResult($service->preflight());
        }

        if ($this->requestObject->method === 'POST') {
            // login and get token
            // eg : { "user": "test@example.com", "password":"Sample#123456"}

            $logindata = json_decode($this->getRequestBody());

            //TODO : user contains either email of name

            // free variables before response
            $clearPassword = $service->generateRandomString(20);

            $response = $service->resetPassword($logindata->{'user'}, $clearPassword);

            if ($response->getCode() === 200) {
                $u = new MailUtils();

                if ($this->enablemail) {
                    $CR_Mail = @mail(
                        $logindata->{'user'},
                        'new password',
                        $u->getNewPassword('new password', $clearPassword, $this->getClientInfo()),
                        $u->getHeaders($this->getConf()->{'mailsender'})
                    );

                    if ($CR_Mail === false) {
                        $response->setError(500, $CR_Mail);
                    } else {
                        $response->setCode(200);
                    }
                } elseif ($this->debugResetPassword) {
                    $tmpResponse = json_decode($response->getResult());
                    $tmpResponse->{'notification'} = $clearPassword;
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

        // Preflight requests are send by Angular
        if ($this->requestObject->method === 'OPTIONS') {
            // eg : /authapi/v1/auth
            $response->setResult($service->preflight());
        }

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

        // Preflight requests are send by Angular
        if ($this->requestObject->method === 'OPTIONS') {
            $response->setResult($service->preflight());
        }

        // register and create a user
        if ($this->requestObject->method === 'POST') {
            $user = json_decode($this->getRequestBody());
            //returns a empty string if success, a string with the message otherwise

            $createresult = $service->createUserWithSecret(
                $user->{'name'},
                $user->{'email'},
                $user->{'password'},
                $user->{'secretQuestion'},
                $user->{'secretResponse'},
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

        header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

        return $response;
    }

    /**
     * Get IP and user agent from client.
     *
     * @return string IP and user agent
     */
    public function getClientInfo(): string
    {
        return $this->getClientIp() . ' ' . $_SERVER['HTTP_USER_AGENT'];
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

            $service = new \mobilecms\utils\UserService($this->getPrivateDirPath() . '/users');

            // Preflight requests are send by Angular
            if ($this->requestObject->method === 'OPTIONS') {
                // eg : /authapi/v1/auth
                $response = $service->preflight();
            }

            if ($this->requestObject->method === 'POST') {
                if (empty($this->getRequestBody())) {
                    throw new \Exception('no login request');
                }
                // login and get token
                // eg : { "user": "test@example.com", "password":"Sample#123456"}
                $logindata = json_decode($this->getRequestBody());

                //TODO : user contains either email of name
                if (!isset($logindata)) {
                    throw new \Exception('no login data');
                }
                $response = $service->getToken($logindata->{'user'}, $logindata->{'password'});
                unset($logindata);
                // free variables before response
            }
        } catch (\Exception $e) {
            $response->setError(401, $e->getMessage());
        } finally {
            return $response;
        }
    }


    /**
     * Get IP address.
     *
     * @return string IP address
     */
    public function getClientIp(): string
    {
        $ipaddress = '';
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
