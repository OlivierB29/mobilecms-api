<?php
/*
 * Core REST Api without Authentication or API Key
 * (Authentication : see SecureRestApi)
 * based on http://coreymaynard.com/blog/creating-a-restful-api-with-php/
 */
abstract class RestApi {
	/**
	 * JSON object with configuration
	 */
	protected $conf;

	/**
	 * set to false when unit testing
	 */
	private $enableHeaders = true;

	/**
	 * Property: method
	 * The HTTP method this request was made in, either GET, POST, PUT or DELETE
	 */
	protected $method = '';
	/**
	 * Property: endpoint
	 * The Model requested in the URI.
	 * eg: /files
	 */
	protected $endpoint = '';
	/**
	 * Property: verb
	 * An optional additional descriptor about the endpoint, used for things that can
	 * not be handled by the basic methods.
	 * eg: /files/process
	 */
	protected $verb = '';

	/**
	 * Property: apiversion
	 * eg : v1
	 */
	protected $apiversion = '';
	/**
	 * Property: args
	 * Any additional URI components after the endpoint and verb have been removed, in our
	 * case, an integer ID for the resource.
	 * eg: /<endpoint>/<verb>/<arg0>/<arg1>
	 * or /<endpoint>/<arg0>
	 */
	protected $args = array ();
	/**
	 * Property: file
	 * Stores the input of the PUT request
	 */
	protected $file = null;

	protected $request = null;

	/**
	 * /api/v1/content/save
	 * eg : /api/v1/recipe/cake/foo/bar
	 */
	public function setRequestUri($request) {
		$this->args = explode ( '/', rtrim ( ltrim ( $request, '/' ), '/' ) );
		// eg : api
		array_shift ( $this->args );

		// eg : v1
		if (array_key_exists ( 0, $this->args )) {
			$this->apiversion = array_shift ( $this->args );
		}

		// eg : recipe
		if (array_key_exists ( 0, $this->args )) {
			$this->endpoint = array_shift ( $this->args );
		}

		// eg : cake
		if (array_key_exists ( 0, $this->args )) {
			$this->verb = array_shift ( $this->args );
		}

		// $this->args contains the remaining elements
		// eg:
		// [0] => foo
		// [1] => bar
	}
	public function __construct($conf) {
		if (isset ( $conf )) {
			$this->conf = $conf;
		} else {
			throw new Exception ( "Empty conf" );
		}

		// Default value is true
		if ('false' === $this->conf->{'enableheaders'}) {
			$this->enableHeaders = false;
		}

		// Default headers for RESTful API
		if ($this->enableHeaders) {
			header ( "Access-Control-Allow-Methods: *" );
			header ( "Content-Type: application/json" );
		}
	}

	/**
	 * initialize parameters with request
	 */
	public function setRequest(array $REQUEST = null, array $SERVER = null, array $GET = null, array $POST = null) {

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

		//
		// Parse URI
		//
		$this->setRequestUri ( $REQUEST ['path'] );

		//
		// detect method
		//
		$this->method = $SERVER ['REQUEST_METHOD'];
		if ($this->method == 'POST' && array_key_exists ( 'HTTP_X_HTTP_METHOD', $SERVER )) {
			if ($SERVER ['HTTP_X_HTTP_METHOD'] == 'DELETE') {
				$this->method = 'DELETE';
			} elseif ($SERVER ['HTTP_X_HTTP_METHOD'] == 'PUT') {
				$this->method = 'PUT';
			} else {
				throw new Exception ( "Unexpected Header" );
			}
		}

		switch ($this->method) {
			case 'DELETE' :
			case 'POST' :
				$this->request = $this->_cleanInputs ( $POST );
				break;
			case 'OPTIONS' :
					$this->options();
			case 'GET' :
				$this->request = $this->_cleanInputs ( $GET );
				break;
			case 'PUT' :
				$this->request = $this->_cleanInputs ( $GET );
				// http://php.net/manual/en/wrappers.php.php
				$this->file = file_get_contents ( "php://input" );
				break;
			default :
				$this->_response ( 'Invalid Method', 405 );
				break;
		}

		return;
	}

	public function getRequest() {
		return $this->request;
	}

	public abstract function options();


	/**
	 * Parse class, and call the method with the endpoint name
	 */
	public function processAPI(): string {
		if (method_exists ( $this, $this->endpoint )) {
			return $this->_response ( $this->{$this->endpoint} ( $this->args ) );
		}
		return $this->_response ( "No Endpoint: $this->endpoint", 404 );
	}

	/**
	 * send JSON response
	 */
	private function _response($data  = "", $status = 200): string {
		if ($this->enableHeaders) {
			header ( "HTTP/1.1 " . $status . " " . $this->_requestStatus ( $status ) );
		}

		//each endpoint should prepare an encoded response
		return $data;
	}
	private function _cleanInputs($data) {
		$clean_input = array ();
		if (is_array ( $data )) {
			foreach ( $data as $k => $v ) {
				$clean_input [$k] = $this->_cleanInputs ( $v );
			}
		} else {
			$clean_input = trim ( strip_tags ( $data ) );
		}
		return $clean_input;
	}
	private function _requestStatus($code) {
		$status = array (
				200 => 'OK',
				404 => 'Not Found',
				405 => 'Method Not Allowed',
				500 => 'Internal Server Error'
		);
		return ($status [$code]) ? $status [$code] : $status [500];
	}
}
