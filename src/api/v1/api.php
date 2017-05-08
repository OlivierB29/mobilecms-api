<?php
/*
 * REST Api
 * based on http://coreymaynard.com/blog/creating-a-restful-api-with-php/
 */
include 'conf.php';
include 'CmsApi.php';

if (null !== ERROR_LOG && ERROR_LOG === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
    ini_set('log_errors', 'On');
}


//
// HTTPS
//
if (null !== ACTIVATE_HTTPS && ACTIVATE_HTTPS === 'true') {
	//http://stackoverflow.com/questions/85816/how-can-i-force-users-to-access-my-page-over-https-instead-of-http/12145293#12145293
	// iis sets HTTPS to 'off' for non-SSL requests
	if ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
	    header('Strict-Transport-Security: max-age=31536000');
	} else {
	    header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], true, 301);
	    // we are in cleartext at the moment, prevent further execution and output
			die();
	}
}

// cross domain !
if (null !== ALLOW_CROSS_DOMAIN && ALLOW_CROSS_DOMAIN === 'true') {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');


// Requests from the same server don't have a HTTP_ORIGIN header
if (! array_key_exists('HTTP_ORIGIN', $_SERVER)) {
    $_SERVER ['HTTP_ORIGIN'] = $_SERVER ['SERVER_NAME'];
}

try {//PRIVATEDIR . '/users'
    $conf = json_decode('{"enableheaders" : "","enableapikey" : "true", "publicdir":"", "privatedir":"" , "apikeyfile" : "" }');
    $conf->{'enableheaders'} = 'true';
    $conf->{'enableapikey'} = 'false';
    $conf->{'publicdir'} = HOME . '/public';
    $conf->{'privatedir'} = PRIVATEDIR ;
    //$conf->{'apikeyfile'} = HOME . '/private/apikeys/key1.json';


    // echo print_r($_REQUEST);

    $API = new CmsApi($conf);

    // $API->setRequest($_REQUEST, $_SERVER, $_GET, $_POST);
    $API->setRequest();
    $API->authorize();

    echo $API->processAPI();
} catch (Exception $e) {
    echo json_encode(array(
            'error' => $e->getMessage()
    ));
}
