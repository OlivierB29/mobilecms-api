<?php
require_once 'Headers.php';
require_once 'RestApi.php';
require_once 'ApiKey.php';
require_once 'UserService.php';


/**
 * Secure implementation of a REST api.
 * - apikey management
 * - JWT
 */
abstract class SecureRestApi extends RestApi {
	public function __construct($conf) {
		parent::__construct ( $conf );
	}
	public function authorize(array $headers = null, array $SERVER = null) {

	switch ($this->method) {
		case 'DELETE' :
		case 'POST' :
		case 'PUT' :
			$this->doAuthorize($headers, $SERVER);
			break;
		case 'OPTIONS' :
			break;
		case 'GET' :
			$this->doAuthorize($headers, $SERVER);
			break;
		default :
			$this->_response ( 'Invalid Method', 405 );
			break;
	}
}
	public function doAuthorize(array $headers = null, array $SERVER = null) {





		if (!isset($SERVER)) {
			$SERVER = &$_SERVER;
		}

		if (!isset($headers)) {
			$headers = getallheaders ();
		}

		//
		// API KEY
		//

		// api key provided ?
		if($this->conf->{'enableapikey'} === 'true') {
			if (array_key_exists ( 'apiKey', $this->request ) || array_key_exists ( 'apiKey', $headers )) {
				$origin = '';
				if (array_key_exists ( 'HTTP_ORIGIN', $SERVER )) {
					$origin = $SERVER ['HTTP_ORIGIN'];
				}
				if (strlen ( $origin ) == 0) {
					$origin = $SERVER ['SERVER_NAME'];
				}

				$apiKeyValue = '';

				// from request or header
				if (array_key_exists ( 'apiKey', $this->request )) {
					$apiKeyValue = $this->request ['apiKey'];
				} elseif (array_key_exists ( 'apiKey', $headers )) {
					$apiKeyValue = $headers ['apiKey'];
				}

				// api key not empty
				if (strlen ( $apiKeyValue ) === 0) {
					throw new Exception ( 'Empty API Key' );
				}

				// verify key
				$APIKey = new ApiKey ();
				$verifyKeyResult = $APIKey->verifyKey ( $this->conf->{'apikeyfile'}, $apiKeyValue, $origin );
				unset ( $APIKey );
				if (! $verifyKeyResult) {
					throw new Exception ( 'Invalid API Key' );
				}
			} else {
				throw new Exception ( 'No API Key provided' );
			}
		}
		//
		// USER TOKEN
		//
		if ((isset ( $this->request ) && array_key_exists ( 'Authorization', $this->request )) || (isset ( $headers ) && array_key_exists ( 'Authorization', $headers ))) {
			$bearerTokenValue = '';
			// from request or header
			if (array_key_exists ( 'Authorization', $this->request )) {
				$bearerTokenValue = $this->request ['Authorization'];
			} elseif (array_key_exists ( 'Authorization', $headers )) {
				$bearerTokenValue = $headers ['Authorization'];
			}


			if (strlen ( $bearerTokenValue ) === 0) {
				throw new Exception ( 'empty token' );
			}

			$tokenValue = $this->getBearerTokenValue ( $bearerTokenValue );

			// verify token

			$service = new UserService ( $this->conf->{'privatedir'} . '/users' );
			$response = $service->verifyToken ( $tokenValue );
			unset ( $service );
			if ($response->getCode () !== 200) {
				throw new Exception ( 'Invalid User Token' . $response->getCode () . $response->getMessage () );
			}
		} else {
			throw new Exception ( 'No User Token provided' );
		}
	}
	private function getBearerTokenValue($headers) {

		// HEADER: Get the access token from the header
		if (! empty ( $headers )) {
			if (preg_match ( '/Bearer\s(\S+)/', $headers, $matches )) {
				return $matches [1];
			}
		}
		return null;
	}

	/**
	 * Get hearder Authorization
	 */
	private function getAuthorizationHeader($SERVER) {
		$headers = null;
		if (isset ( $SERVER ['Authorization'] )) {
			$headers = trim ( $SERVER ["Authorization"] );
		} elseif (isset ( $SERVER ['HTTP_AUTHORIZATION'] )) { // Nginx or fast CGI
			$headers = trim ( $SERVER ["HTTP_AUTHORIZATION"] );
		} elseif (function_exists ( 'apache_request_headers' )) {
			$requestHeaders = apache_request_headers ();
			// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
			$requestHeaders = array_combine ( array_map ( 'ucwords', array_keys ( $requestHeaders ) ), array_values ( $requestHeaders ) );
			// print_r($requestHeaders);
			if (isset ( $requestHeaders ['Authorization'] )) {
				$headers = trim ( $requestHeaders ['Authorization'] );
			}
		}
		return $headers;
	}

	/**
	 * get access token from header
	 */
	private function getBearerToken($SERVER) {
		$headers = $this->getAuthorizationHeader ( $SERVER );
		// HEADER: Get the access token from the header
		if (! empty ( $headers )) {
			if (preg_match ( '/Bearer\s(\S+)/', $headers, $matches )) {
				return $matches [1];
			}
		}
		return null;
	}
}
