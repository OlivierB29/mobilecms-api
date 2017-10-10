<?php

require_once 'Response.php';

/*
 * Core REST Api without Authentication or API Key
 * (Authentication : see SecureRestApi)
 * based on http://coreymaynard.com/blog/creating-a-restful-api-with-php/
 */
abstract class RestApi
{
    /**
     * if needed : post form data instead of php://input.
     */
    const REQUESTBODY = 'requestbody';

    /**
     * if needed : post form data instead of php://input.
     */
    protected $postformdata = false;

    /**
     * JSON object with configuration.
     */
    protected $conf;

    /**
     * set to false when unit testing.
     */
    protected $enableHeaders = true;

    /**
     * see cleanInputs() below.
     */
    protected $enableCleanInputs = true;

    /**
     * Property: method
     * The HTTP method this request was made in, either GET, POST, PUT or DELETE.
     */
    protected $method = '';
    /**
     * Property: endpoint
     * The Model requested in the URI.
     * eg: /files.
     */
    protected $endpoint = '';
    /**
     * Property: verb
     * An optional additional descriptor about the endpoint, used for things that can
     * not be handled by the basic methods.
     * eg: /files/process.
     */
    protected $verb = '';

    /**
     * Property: apiversion
     * eg : v1.
     */
    protected $apiversion = '';
    /**
     * Property: args
     * Any additional URI components after the endpoint and verb have been removed, in our
     * case, an integer ID for the resource.
     * eg: /<endpoint>/<verb>/<arg0>/<arg1>
     * or /<endpoint>/<arg0>.
     */
    protected $args = [];
    /**
     * Property: file
     * Stores the input of the PUT request.
     */
    protected $file = null;

    /**
     * request content from post data or JSON body.
     */
    protected $request = null;

    /**
     * headers array.
     */
    protected $headers = null;

    /**
     * when enabled : send readable errors in responses.
     */
    protected $displayApiErrors = true;

    /**
     * root app dir.
     */
    protected $rootDir = '';

    /**
     * /api/v1/content/save
     * eg : /restapi/v1/recipe/cake/foo/bar.
     * http://localhost/restapi/v1/file/?file=news/index/metadata.json.
     */
    public function setRequestUri($request)
    {
        $this->args = explode('/', rtrim(ltrim($request, '/'), '/'));
        // eg : api
        array_shift($this->args);

        // eg : v1
        if (array_key_exists(0, $this->args)) {
            $this->apiversion = array_shift($this->args);
        }

        //TODO better parse.
        // issue when restapi/v1/file?file=news/index/metadata.json
        // instead, use restapi/v1/file/?file=news/index/metadata.json
        //
        // eg : recipe
        if (array_key_exists(0, $this->args)) {
            $this->endpoint = array_shift($this->args);
        }

        // eg : cake
        if (array_key_exists(0, $this->args)) {
            $this->verb = array_shift($this->args);
        }

        // $this->args contains the remaining elements
        // eg:
        // [0] => foo
        // [1] => bar
    }

    /**
     * @param conf JSON configuration
     */
    public function __construct(stdClass $conf)
    {
        if (isset($conf)) {
            $this->conf = $conf;
        } else {
            throw new Exception('Empty conf');
        }

        // Default value is true
        if (!empty($this->conf->{'enableheaders'}) && 'false' === $this->conf->{'enableheaders'}) {
            $this->enableHeaders = false;
        }

        // Default value is true
        if (!empty($this->conf->{'enablecleaninputs'}) && 'false' === $this->conf->{'enablecleaninputs'}) {
            $this->enableCleanInputs = false;
        }

        // Default value is true
        if (!empty($this->conf->{'postformdata'}) && 'true' === $this->conf->{'postformdata'}) {
            $this->postformdata = true;
        }

        if ($this->enableHeaders) {
            if (!empty($this->conf->{'crossdomain'}) && 'true' === $this->conf->{'crossdomain'}) {
                header('Access-Control-Allow-Origin: *');
            }

            header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');

            // Requests from the same server don't have a HTTP_ORIGIN header
            if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
                $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
            }

            if (!empty($this->conf->{'https'}) && 'true' === $this->conf->{'https'}) {
                //
                // HTTPS
                //

                //http://stackoverflow.com/questions/85816/how-can-i-force-users-to-access-my-page-over-https-instead-of-http/12145293#12145293
                // iis sets HTTPS to 'off' for non-SSL requests
                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
                    header('Strict-Transport-Security: max-age=31536000');
                } else {
                    header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], true, 301);
                    // we are in cleartext at the moment, prevent further execution and output
                    die();
                }
            }
        }

        if (!empty($this->conf->{'errorlog'}) && 'true' === $this->conf->{'errorlog'}) {
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
            ini_set('log_errors', 'On');
        }

        $this->rootDir = $_SERVER['DOCUMENT_ROOT'];
    }

    /**
     * Initialize parameters with request.
     * Important : the variables are initialized in unit tests.
     * In real case, use null and the PHP variables will be used.
     *
     * @param REQUEST : must be the same content like the PHP variable
     * @param SERVER : must be the same content like the PHP variable
     * @param GET : must be the same content like the PHP variable
     * @param POST : must be the same content like the PHP variable
     * @param headers : http headers
     */
    public function setRequest(array $REQUEST = null, array $SERVER = null, array $GET = null, array $POST = null, array $headers = null)
    {

        // Useful for tests http://stackoverflow.com/questions/21096537/simulating-http-request-for-unit-testing

        // set reference to avoid objet clone
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

        $this->headers = $headers;

        // Parse URI
        $this->setRequestUri($SERVER['REQUEST_URI']);

        // detect method
        $this->method = $SERVER['REQUEST_METHOD'];
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $SERVER)) {
            if ($SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } elseif ($SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new Exception('Unexpected Header');
            }
        }

        switch ($this->method) {
            case 'DELETE':
            case 'POST':
                if ($this->postformdata === true) {
                    $this->request = $this->enableCleanInputs ? $this->cleanInputs($POST) : $POST;
                } else {
                    $this->request = $this->enableCleanInputs ? $this->cleanInputs(file_get_contents('php://input')) : file_get_contents('php://input');
                }
                break;
            case 'OPTIONS':
                    $this->preflight();
                break;
            case 'GET':
                $this->request = $this->enableCleanInputs ? $this->cleanInputs($GET) : $GET;
                break;
            case 'PUT':
                $this->request = $this->enableCleanInputs ? $this->cleanInputs($GET) : $GET;
                //$this->request = $this->cleanInputs($GET);
                // http://php.net/manual/en/wrappers.php.php

                break;
            default:
                $this->_response('Invalid Method', 405);
                break;
        }
    }

    /**
     * get current request.
     *
     * @return request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Preflight requests are send by client framework, such as Angular
     * Example :
     * header("Access-Control-Allow-Methods: *");
     * header("Access-Control-Allow-Headers: Content-Type,
     *   Access-Control-Allow-Headers, Authorization, X-Requested-With");.
     *
     * @return response object
     */
    abstract public function preflight(): Response;

    /**
     * Parse class, and call the method with the endpoint name.
     *
     * @return response object
     */
    public function processAPI(): Response
    {
        $apiResponse = $this->getDefaultResponse();
        if (method_exists($this, $this->endpoint)) {
            $apiResponse = $this->{$this->endpoint}($this->args);
        }

        return $apiResponse;
    }

    /**
     * main function
     * - parse request
     * - execute backend
     * - send response or error.
     */
    public function execute()
    {
        $status = 400;
        $responseBody = null;

        try {
            $this->setRequest();

            $response = $this->processAPI();
            $responseBody = $response->getResult();
            $status = $response->getCode();
        } catch (Exception $e) {
            // security : clear variables on exception

            $status = 500;
            error_log($e->getMessage());
            if ($this->displayApiErrors) {
                $responseBody = json_encode(['error' => $e->getMessage()]);
            } else {
                // security : should not display to much error reporting to an attacker
                $responseBody = json_encode(['error' => 'internal error']);
            }
        } finally {
            http_response_code($status);
            echo $responseBody;
        }
    }

    /**
     * sanitize data.
     *
     * @param data resquest body
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
     * initialize a default Response object.
     *
     * @return response object
     */
    protected function getDefaultResponse() : Response
    {
        $response = new Response();
        $response->setCode(400);
        $response->setResult('{}');

        return $response;
    }

    /**
     * get request body.
     *
     * @return post form data or JSON data
     */
    public function getRequestBody(): string
    {
        if ($this->postformdata === true) {
            return $this->request[self::REQUESTBODY];
        } else {
            return $this->request;
        }
    }

    /**
     * set main working directory.
     *
     * @param rootDir main working directory
     */
    public function setRootDir(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * get main working directory.
     *
     * @return rootDir main working directory
     */
    public function getRootDir(): string
    {
        return $this->rootDir;
    }

    /**
     * get public directory.
     *
     * @param publicdir main public directory
     */
    public function getPublicDirPath(): string
    {
        return $this->rootDir.$this->conf->{'publicdir'};
    }

    /**
     * get privatedir directory.
     *
     * @param privatedir main privatedir directory
     */
    public function getPrivateDirPath(): string
    {
        return $this->rootDir.$this->conf->{'privatedir'};
    }
}
