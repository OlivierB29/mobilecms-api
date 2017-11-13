<?php namespace mobilecms\utils;

/**
 * Secure implementation of a REST api.
 * - apikey management
 * - JWT.
 */
abstract class SecureRestApi extends RestApi
{
    /**
     * Authorization token value.
     */
    const AUTHORIZATION = 'Authorization';

    /**
     * HTTP_AUTHORIZATION token value.
     */
    const HTTP_AUTHORIZATION = 'HTTP_AUTHORIZATION';

    /**
     * Required role for read / write throug API.
     */
    private $role = 'editor';

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
     * Override parent function.
     *
     * @return Response object
     */
    public function processAPI(): Response
    {
        $response = $this->getDefaultResponse();

        // authorize() return a response with 200. Otherwise :
        // - throw an exception
        // - return a response
        try {
            $response = $this->authorize();
        } catch (\Exception $e) {
            $response->setError(401, $e->getMessage());
        }

        if ($response->getCode() === 200) {
            return parent::processAPI();
        }

        return $response;
    }

    /**
     * $headers : array containing result of apache_request_headers() and getallheaders(), if available.
     * Or send by test units.
     *
     * @param array $headers : send by test units.
     * @param array $SERVER  : send by test units.
     */
    public function authorize(array $headers = null, array $SERVER = null): Response
    {
        $response = $this->getDefaultResponse();
        $response->setCode(401);

        switch ($this->requestObject->method) {
            case 'OPTIONS':
                  $response->setCode(200);
                  $response->setResult('{}');
                break;
            case 'GET':
            case 'POST':
            case 'PUT':
            case 'DELETE':
                $response = $this->doAuthorize($headers, $SERVER);
                break;
            default:
                $response->getCode(405);
                break;
        }

        return $response;
    }

    /**
     * Set required role.
     *
     * @param string $role editor, admin, ...
     */
    public function setRole(string $role)
    {
        $this->role = $role;
    }

    /**
     * Authorize current user.
     *
     * @param array $SERVER : send by test units.
     *
     * @return Response object
     */
    public function doAuthorize(array $SERVER = null)
    {
        $response = $this->getDefaultResponse();
        $response->setCode(401);

        if (!isset($SERVER)) {
            $SERVER = &$_SERVER;
        }

        //
        // API KEY
        //

        // api key provided ?
        if ($this->getConf()->{'enableapikey'} === 'true' && isset($headers)) {
            if (array_key_exists('apiKey', $this->request) || array_key_exists('apiKey', $headers)) {
                $origin = '';
                if (array_key_exists('HTTP_ORIGIN', $SERVER)) {
                    $origin = $SERVER['HTTP_ORIGIN'];
                }
                if (strlen($origin) == 0) {
                    $origin = $SERVER['SERVER_NAME'];
                }

                $apiKeyValue = '';

                // from request or header
                if (array_key_exists('apiKey', $this->request)) {
                    $apiKeyValue = $this->request['apiKey'];
                } elseif (array_key_exists('apiKey', $headers)) {
                    $apiKeyValue = $headers['apiKey'];
                }

                // api key not empty
                if (strlen($apiKeyValue) === 0) {
                    throw new \Exception('Empty API Key');
                }

                // verify key
                $APIKey = new ApiKey();
                $verifyKeyResult = $APIKey->verifyKey($this->getConf()->{'apikeyfile'}, $apiKeyValue, $origin);
                unset($APIKey);
                if (!$verifyKeyResult) {
                    throw new \Exception('Invalid API Key');
                }
            } else {
                throw new \Exception('No API Key provided');
            }
        }

        // USER TOKEN
        //string containing Bearer prefix and value eg : Bearer abcdef.abcdef....
        $bearerTokenValue = $this->getAuthorizationHeader();

        //for unit tests
        if (!empty($this->requestObject->headers)) {
            $bearerTokenValue = $this->requestObject->headers[self::AUTHORIZATION];
        }

        if (!empty($bearerTokenValue)) {
            $tokenValue = $this->getBearerTokenValue($bearerTokenValue);

            if (empty($tokenValue)) {
                throw new \Exception('Empty token !' . $bearerTokenValue);
            }
            unset($bearerTokenValue);

            // verify token
            $service = new UserService($this->getPrivateDirPath() . '/users');
            $response = $service->verifyToken($tokenValue, $this->role);

            unset($service);
        } else {
            throw new \Exception('No User Token provided');
        }

        return $response;
    }

    /**
     * Get header Authorization
     * When apache_request_headers() and getallheaders() functions are not defined
     * http://stackoverflow.com/questions/2916232/call-to-undefined-function-apache-request-headers.
     *
     *
     * Use a .htaccess file for generating HTTP_AUTHORIZATION :
     * http://php.net/manual/en/function.apache-request-headers.php
     *
     * @param array $SERVER : send same content as PHP variable when testing
     *
     * @return array headers
     */
    private function getAuthorizationHeader($SERVER = null)
    {
        if (!isset($SERVER)) {
            $SERVER = &$_SERVER;
        }

        $headers = null;
        if (isset($SERVER[self::AUTHORIZATION])) {
            $headers = trim($SERVER[self::AUTHORIZATION]);
        } elseif (isset($SERVER[self::HTTP_AUTHORIZATION])) { // Nginx or fast CGI
            $headers = trim($SERVER[self::HTTP_AUTHORIZATION]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions
            // A nice side-effect of this fix means we don't care about capitalization for Authorization
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );

            if (isset($requestHeaders[self::AUTHORIZATION])) {
                $headers = trim($requestHeaders[self::AUTHORIZATION]);
            }
        }

        return $headers;
    }

    /**
     *  Get token from headers.
     *
     * @param string $headerValue header value
     *
     * @return string token value
     */
    private function getBearerTokenValue(string $headerValue): string
    {
        // HEADER: Get the access token from the header
        if (!empty($headerValue)) {
            if (preg_match('/Bearer\s(\S+)/', $headerValue, $matches)) {
                return $matches[1];
            }
        }
    }

    /**
     * Get access token from SERVER.
     *
     * @param array $SERVER : send by test units.
     *
     * @return string token value
     */
    private function getBearerToken(array $SERVER = null): string
    {
        $headers = $this->getAuthorizationHeader($SERVER);
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
    }
}
