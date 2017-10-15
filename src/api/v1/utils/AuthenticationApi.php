<?php

require_once 'RestApi.php';
require_once 'UserService.php';
require_once 'MailUtils.php';

/*
 * Login API
 * /authapi/v1/auth
 * /authapi/v1/register
 */
class AuthenticationApi extends RestApi
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
     * @param stdClass $conf JSON configuration
     */
    public function __construct(stdClass $conf)
    {
        parent::__construct($conf);

        // Default value is true
        if (!empty($this->conf->{'debugnotifications'}) && 'true' === $this->conf->{'debugnotifications'}) {
            $this->debugResetPassword = true;
        }

        if (!empty($this->conf->{'enablemail'}) && 'true' === $this->conf->{'enablemail'}) {
            $this->enablemail = true;
        }
    }



    /**
     * Base API path /authapi/v1/changepassword.
     *
     * @return Response object
     */
    protected function changepassword() : Response
    {
        $response = $this->getDefaultResponse();

        //throw error if wrong configuration, such as empty directory
        $this->checkConfiguration();

        $service = new UserService($this->getPrivateDirPath() . '/users');

        // Preflight requests are send by Angular
        if ($this->method === 'OPTIONS') {
            // eg : /authapi/v1/auth
            $response->setResult($service->preflight());
        }

        if ($this->method === 'POST') {
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
     * @return Response object
     */
    protected function resetpassword() : Response
    {
        $response = $this->getDefaultResponse();

        //throw error if wrong configuration, such as empty directory
        $this->checkConfiguration();

        $service = new UserService($this->getPrivateDirPath() . '/users');

        // Preflight requests are send by Angular
        if ($this->method === 'OPTIONS') {
            // eg : /authapi/v1/auth
            $response->setResult($service->preflight());
        }

        if ($this->method === 'POST') {
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
                        $u->getHeaders($this->conf->{'mailsender'})
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
     * @return Response object
     */
    protected function publicinfo() : Response
    {
        $response = $this->getDefaultResponse();

        //throw error if wrong configuration, such as empty directory
        $this->checkConfiguration();

        $service = new UserService($this->getPrivateDirPath() . '/users');

        // Preflight requests are send by Angular
        if ($this->method === 'OPTIONS') {
            // eg : /authapi/v1/auth
            $response->setResult($service->preflight());
        }

        if ($this->method === 'GET') {
            $id = '';
            if (isset($this->verb)) {
                $id = $this->verb;
            }
            $response = $service->getPublicInfo($id);
            unset($user);
        }

        return $response;
    }

    /**
     * /authapi/v1/register.
     *
     * @return Response object
     */
    protected function register() : Response
    {
        $response = $this->getDefaultResponse();

        //throw error if wrong configuration, such as empty directory
        $this->checkConfiguration();
        $service = new UserService($this->getPrivateDirPath() . '/users');

        // Preflight requests are send by Angular
        if ($this->method === 'OPTIONS') {
            $response->setResult($service->preflight());
        }

        // register and create a user
        if ($this->method === 'POST') {
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
     * @return Response object
     */
    public function preflight(): Response
    {
        $response = new Response();
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
         * @return Response object
         */
    protected function authenticate() : Response
    {
        $response = $this->getDefaultResponse();

        try {
            // error if wrong configuration, such as empty directory
            $this->checkConfiguration();

            $service = new UserService($this->getPrivateDirPath() . '/users');

            // Preflight requests are send by Angular
            if ($this->method === 'OPTIONS') {
                // eg : /authapi/v1/auth
                $response = $service->preflight();
            }

            if ($this->method === 'POST') {
                if (empty($this->getRequestBody())) {
                    throw new Exception('no login request');
                }
                // login and get token
                // eg : { "user": "test@example.com", "password":"Sample#123456"}
                $logindata = json_decode($this->getRequestBody());

                //TODO : user contains either email of name
                if (!isset($logindata)) {
                    throw new Exception('no login data');
                }
                $response = $service->getToken($logindata->{'user'}, $logindata->{'password'});
                unset($logindata);
                // free variables before response
            }
        } catch (Exception $e) {
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
        if (!isset($this->conf->{'privatedir'})) {
            throw new Exception('Empty privatedir');
        }
    }

    /**
     * Get id from URI.
     *
     * @return string id from URI
     */
    private function getId(): string
    {
        $result = '';
        if (isset($this->args) && array_key_exists(0, $this->args)) {
            $result = $this->args[0];
        }

        return $result;
    }
}
