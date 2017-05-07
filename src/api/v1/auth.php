<?php
/*
 * REST Api
 * based on http://coreymaynard.com/blog/creating-a-restful-api-with-php/
 */
include 'conf.php';
include 'utils/UserService.php';

// cross domain !
if (null !== ALLOW_CROSS_DOMAIN && ALLOW_CROSS_DOMAIN === 'true') {
	header ( 'Access-Control-Allow-Origin: *' );
}

// Requests from the same server don't have a HTTP_ORIGIN header
if (! array_key_exists ( 'HTTP_ORIGIN', $_SERVER )) {
	$_SERVER ['HTTP_ORIGIN'] = $_SERVER ['SERVER_NAME'];
}

try {
	//
	// default response values
	//
	$status = 400;
	$statusMsg = '';
	$response = '{}';


		if($_SERVER ['REQUEST_METHOD'] === 'OPTIONS')
		{
				header("Access-Control-Allow-Methods: *");
				header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
				return '{}';
		}


	if ($_SERVER ['REQUEST_METHOD'] === 'POST') {
		if (array_key_exists ( 'requestbody', $_POST )) {
			$service = new UserService ( PRIVATEDIR . '/users' );

			$method = $_SERVER ['REQUEST_METHOD'];

			//
			//Preflight requests are send by Angular
			//
			if ($method === 'OPTIONS') {
					//eg : /api/v1/content
					$response =  $service->preflight();
			} else {
				//
				// eg : requestbody={ "user": "test@example.com", "password":"Sample#123456"}
				//
				$logindata = json_decode ( $_POST ['requestbody'] );
				$result = $service->getToken ( $logindata->{'user'}, $logindata->{'password'} );

				$status = $result->getCode ();
				$response = $result->getResult ();
				// free variables before response
				unset ( $logindata );
				unset ( $result );

			}
			unset ( $service );

		} else {
			$status = 401;
			$statusMsg = 'Wrong Login' ;
		}
	}
} catch ( Exception $e ) {
	$status = 500;
	$statusMsg = 'auth error ' . $e->getMessage ();
	$response = json_encode ( array (
			'error' => $e->getMessage ()
	) );
} finally {
	header ( "HTTP/1.1 " . $status . " " . $statusMsg );
	echo $response;
}
