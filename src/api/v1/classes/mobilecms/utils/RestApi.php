<?php namespace mobilecms\utils;

/*
 * Core REST Api without Authentication or API Key
 * (Authentication : see SecureRestApi)
 * based on http://coreymaynard.com/blog/creating-a-restful-api-with-php/
 */
abstract class RestApi
{
    /**
     * If needed : post form data instead of php://input.
     */
    const REQUESTBODY = 'requestbody';

    /**
     * If needed : post form data instead of php://input.
     */
    protected $postformdata = false;

    /**
    * configuration
    */
    protected $properties ;

    /**
     * Set to false when unit testing.
     */
    protected $enableHeaders = true;

    /**
     * See cleanInputs() below.
     */
    protected $enableCleanInputs = true;

    protected $requestObject;

    /**
     * When enabled : send readable errors in responses.
     */
    protected $displayApiErrors = true;

    /**
     * Root app dir.
     */
    protected $rootDir = '';

    /**
    * url utility
    */
    protected $urlUtils ;



    /**
     * Preflight requests are send by client framework, such as Angular
     * Example :
     * header("Access-Control-Allow-Methods: *");
     * header("Access-Control-Allow-Headers: Content-Type,
     *   Access-Control-Allow-Headers, Authorization, X-Requested-With");.
     *
     * @return Response object
     */
    abstract public function preflight(): Response;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->urlUtils = new UrlUtils();
    }

    public function loadConf(string $file)
    {
        $this->properties = new Properties();
        $this->properties->loadConf($file);
        $this->setConf($this->properties->getConf());
    }

    /**
     * Init configuration.
     *
     * @param \stdClass $conf JSON configuration
     */
    public function setConf()
    {


        // enable of disable header()
        $this->enableHeaders = $this->properties->getBoolean('enableheaders', true);

        // sanitize inputs
        $this->enableCleanInputs = $this->properties->getBoolean('enablecleaninputs', true);

        // Default value is false for RESTful
        $this->postformdata = $this->properties->getBoolean('postformdata', false);

        // Default value is false
        $this->displayApiErrors = $this->properties->getBoolean('debugapiexceptions', true);

        if ($this->enableHeaders) {
            // @codeCoverageIgnoreStart
            if ($this->properties->getBoolean('crossdomain', false)) {
                header('Access-Control-Allow-Origin: *');
            }

            header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');

            // Requests from the same server don't have a HTTP_ORIGIN header
            if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
                $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
            }

            if ($this->properties->getBoolean('https', true)) {
                //
                // HTTPS
                //

                //http://stackoverflow.com/questions/85816/how-can-i-force-users-to-access-my-page-over-https-instead-of-http/12145293#12145293
                // iis sets HTTPS to 'off' for non-SSL requests
                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
                    header('Strict-Transport-Security: max-age=31536000');
                } else {
                    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
                    // we are in cleartext at the moment, prevent further execution and output
                    die();
                }
            }
            // @codeCoverageIgnoreEnd
        }

        if ($this->properties->getBoolean('errorlog', true)) {
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
            ini_set('log_errors', 'On');
        }

        $this->rootDir = $_SERVER['DOCUMENT_ROOT'];
    }



    /**
     * Set request URI eg:
     * /api/v1/content/save
     * /restapi/v1/recipe/cake/foo/bar.
     * http://localhost/restapi/v1/file/?file=news/index/metadata.json.
     *
     * @param string $request uri
     */
    public function setRequestUri(string $request)
    {
        $this->requestObject->uri = $request;

        $this->requestObject->args = explode('/', rtrim(ltrim($request, '/'), '/'));
        // eg : api
        array_shift($this->requestObject->args);

        // eg : v1
        if (array_key_exists(0, $this->requestObject->args)) {
            $this->requestObject->apiversion = array_shift($this->requestObject->args);
        }

        //TODO better parse.
        // issue when restapi/v1/file?file=news/index/metadata.json
        // instead, use restapi/v1/file/?file=news/index/metadata.json
        //
        // eg : recipe
        if (array_key_exists(0, $this->requestObject->args)) {
            $this->requestObject->endpoint = array_shift($this->requestObject->args);
        }

        // eg : cake
        if (array_key_exists(0, $this->requestObject->args)) {
            $this->requestObject->verb = array_shift($this->requestObject->args);
        }

        // $this->requestObject->args contains the remaining elements
       // eg:
       // [0] => foo
       // [1] => bar
    }


    /**
     * Initialize parameters with request.
     * Important : the variables are initialized in unit tests.
     * In real case, use null and the PHP variables will be used.
     *
     * @param array $REQUEST : must be the same content like the PHP variable
     * @param array $SERVER  : must be the same content like the PHP variable
     * @param array $GET     : must be the same content like the PHP variable
     * @param array $POST    : must be the same content like the PHP variable
     * @param array $headers : http headers
     */
    public function setRequest(
        array $REQUEST = null,
        array $SERVER = null,
        array $GET = null,
        array $POST = null,
        array $headers = null
    ) {

        // Useful for tests
        // http://stackoverflow.com/questions/21096537/simulating-http-request-for-unit-testing

        // set reference to avoid objet clone
        // @codeCoverageIgnoreStart
        if ($SERVER === null) {
            $SERVER = &$_SERVER;
        }
        if ($GET === null) {
            $GET = &$_GET;
        }
        if ($POST === null) {
            $POST = &$_POST;
        }
        if ($REQUEST === null) {
            $REQUEST = &$_REQUEST;
        }
        // @codeCoverageIgnoreEnd
        $this->requestObject = new Request();
        $this->requestObject->headers = $headers;

        // Parse URI

        $this->setRequestUri($this->cleanInputs($SERVER['REQUEST_URI']));

        // detect method
        $this->requestObject->method = $SERVER['REQUEST_METHOD'];
        if ($this->requestObject->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $SERVER)) {
            // @codeCoverageIgnoreStart
            if ($SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->requestObject->method = 'DELETE';
            } elseif ($SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->requestObject->method = 'PUT';
            } else {
                throw new \Exception('Unexpected Header');
            }
            // @codeCoverageIgnoreEnd
        }

        switch ($this->requestObject->method) {
            case 'POST':
                if ($this->postformdata === true) {
                    $this->requestObject->request = $this->enableCleanInputs ? $this->cleanInputs($POST) : $POST;
                } else {
                    // @codeCoverageIgnoreStart
                    $this->requestObject->request = $this->enableCleanInputs ?
                    $this->cleanInputs(file_get_contents('php://input')) : file_get_contents('php://input');
                    // @codeCoverageIgnoreEnd
                }
                break;
            case 'OPTIONS':
            case 'DELETE':
            case 'GET':
                $this->requestObject->request = $this->enableCleanInputs ? $this->cleanInputs($GET) : $GET;
                break;
                  // @codeCoverageIgnoreStart
            case 'PUT':
                $this->requestObject->request = $this->enableCleanInputs ? $this->cleanInputs($GET) : $GET;
                // http://php.net/manual/en/wrappers.php.php

                break;
                // @codeCoverageIgnoreEnd
            default:
            // @codeCoverageIgnoreStart
                throw new \Exception('Invalid Method');
                break;
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Get current request.
     *
     * @return request
     */
    public function getRequest()
    {
        return $this->requestObject->request;
    }



    /**
     * Parse class, and call the method with the endpoint name.
     *
     * @return Response object
     */
    public function processAPI(): Response
    {
        $apiResponse = $this->getDefaultResponse();
        try {
            if (method_exists($this, $this->requestObject->endpoint)) {
                if ($this->requestObject->method === 'OPTIONS') {
                    // eg : /authapi/v1/auth
                    $apiResponse = $this->preflight();
                } else {
                    $apiResponse = $this->{$this->requestObject->endpoint}($this->requestObject->args);
                }
            }
        } catch (\Exception $e) {
            // enable on local development server only https://www.owasp.org/index.php/Improper_Error_Handling
            if ($this->displayApiErrors) {
                $apiResponse->setError(500, $e->getMessage());
            } else {
                // @codeCoverageIgnoreStart
                $apiResponse->setError(500, 'internal error ');
                // @codeCoverageIgnoreEnd
            }
        }

        return $apiResponse;
    }

    /**
     * Main function
     * - parse request
     * - execute backend
     * - send response or error.
     */
    // @codeCoverageIgnoreStart
    public function execute()
    {
        $status = 400;
        $responseBody = null;

        try {
            $this->setRequest();

            $response = $this->processAPI();

            $responseBody = $response->getResult();
            $status = $response->getCode();
            unset($response);
        } catch (\Exception $e) {
            $status = 500;

            error_log($e->getMessage());
            $responseBody = ['error' => 'internal error'];
        } finally {
            if ($this->enableHeaders) {
                // @codeCoverageIgnoreStart
                header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
                // @codeCoverageIgnoreEnd
            }
            http_response_code($status);
            echo json_encode($responseBody);
            // OWASP security : clear variables, especially on exception
            $this->clearRequestParameters();
        }
    }
    // @codeCoverageIgnoreEnd

    /**
    * Clear all request parameters.
    */
    // @codeCoverageIgnoreStart
    private function clearRequestParameters()
    {
        unset($this->requestObject);
    }
    // @codeCoverageIgnoreEnd

    /**
     * Initialize a default Response object.
     *
     * @return Response object
     */
    protected function getDefaultResponse() : Response
    {
        $response = new Response();
        $response->setCode(400);
        $response->setResult(new \stdClass);

        return $response;
    }

    /**
     * Get request body.
     *
     * @return string post form data or JSON data
     */
    public function getRequestBody(): string
    {
        if ($this->postformdata === true) {
            return $this->requestObject->request[self::REQUESTBODY];
        } else {
            // @codeCoverageIgnoreStart
            return $this->requestObject->request;
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Set main working directory.
     *
     * @param string $rootDir main working directory
     */
    public function setRootDir(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * Get main working directory.
     *
     * @return string rootDir main working directory
     */
    public function getRootDir(): string
    {
        return $this->rootDir;
    }

    /**
     * Get public directory.
     *
     * @return string publicdir main public directory
     */
    public function getPublicDirPath(): string
    {
        return $this->rootDir . $this->getConf()->{'publicdir'};
    }

    /**
     * Get public directory.
     *
     * @return string publicdir main public directory
     */
    public function getMediaDirPath(): string
    {
        return $this->rootDir . $this->getConf()->{'media'};
    }

    /**
     * Get privatedir directory.
     *
     * @return string privatedir main privatedir directory
     */
    public function getPrivateDirPath(): string
    {
        return $this->rootDir . $this->getConf()->{'privatedir'};
    }


    /**
     * Sanitize data.
     *
     * @param mixed $data request body
     */
    private function cleanInputs($data)
    {
        $clean_input = [];
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->cleanInputs($v);
            }
        } else {
            $clean_input = trim(strip_tags($data));
        }

        return $clean_input;
    }

    /**
    * get JSON conf
    * @return \stdClass JSON conf
    */
    public function getConf()
    {
        return $this->properties->getConf();
    }


    protected function getParam($key)
    {
        return $this->requestObject->getParam($key);
    }
}
