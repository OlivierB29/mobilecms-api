<?php

require_once 'RestApi.php';
require_once 'ApiKey.php';
require_once 'UserService.php';

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

    public function __construct($conf)
    {
        parent::__construct($conf);
    }

    public function processAPI(): string
    {
        $response = $this->getDefaultResponse();

        // authorize() return a response with 200. Otherwise :
        // - throw an exception
        // - return a response
        try {
            $response = $this->authorize();
        } catch (Exception $e) {
            $response->setCode(401);
            $response->setMessage($e->getMessage());
            $response->setResult($this->errorToJson($e->getMessage()));
        }

        if ($response->getCode() === 200) {
            return parent::processAPI();
        } else {
            return $this->_responseObj($response);
        }
    }

    /**
     * $headers : array containing result of apache_request_headers() and getallheaders(), if available.
     * Or send by test units.
     *
     * $SERVER : send by test units.
     */
    public function authorize(array $headers = null, array $SERVER = null)
    {
        $response = $this->getDefaultResponse();
        $response->setCode(401);

        switch ($this->method) {
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
     * $headers : array containing result of apache_request_headers() and getallheaders(), if available.
     * Or send by test units.
     *
     * $SERVER : send by test units.
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
        if ($this->conf->{'enableapikey'} === 'true' && isset($headers)) {
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
                    throw new Exception('Empty API Key');
                }

                // verify key
                $APIKey = new ApiKey();
                $verifyKeyResult = $APIKey->verifyKey($this->conf->{'apikeyfile'}, $apiKeyValue, $origin);
                unset($APIKey);
                if (!$verifyKeyResult) {
                    throw new Exception('Invalid API Key');
                }
            } else {
                throw new Exception('No API Key provided');
            }
        }

        //
        // USER TOKEN
        //string containing Bearer prefix and value eg : Bearer abcdef.abcdef....
        //
        $bearerTokenValue = $this->getAuthorizationHeader();

        //for unit tests
        if (!empty($this->headers)) {
            $bearerTokenValue = $this->headers[self::AUTHORIZATION];
        }

        if (!empty($bearerTokenValue)) {
            $tokenValue = $this->getBearerTokenValue($bearerTokenValue);

            if (empty($tokenValue)) {
                throw new Exception('Empty token !'.$bearerTokenValue);
            }
            unset($bearerTokenValue);

            // verify token

            $service = new UserService($this->conf->{'privatedir'}.'/users');
            $response = $service->verifyToken($tokenValue);

            unset($service);
        } else {
            throw new Exception('No User Token provided');
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
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            // print_r($requestHeaders);
            if (isset($requestHeaders[self::AUTHORIZATION])) {
                $headers = trim($requestHeaders[self::AUTHORIZATION]);
            }
        }

        return $headers;
    }

    private function getBearerTokenValue($headerValue)
    {

        // HEADER: Get the access token from the header
        if (!empty($headerValue)) {
            if (preg_match('/Bearer\s(\S+)/', $headerValue, $matches)) {
                return $matches[1];
            }
        }
    }

    /**
     * get access token from header.
     */
    private function getBearerToken($SERVER = null)
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
