<?php
/*
* REST Api
* based on http://coreymaynard.com/blog/creating-a-restful-api-with-php/
*/
include 'conf.php';
include 'CmsApi.php';

// cross domain !
if (null !== ALLOW_CROSS_DOMAIN && ALLOW_CROSS_DOMAIN === 'true') {
    header('Access-Control-Allow-Origin: *');
}


// Requests from the same server don't have a HTTP_ORIGIN header
if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

try {

    $conf = json_decode('{"enableheaders" : "true", "publicdir":"'.HOME.'/public", "privatedir":"'.HOME.'/private" , "apikeyfile" : "' . HOME .'/private/apikeys/key1.json" }');


  //  echo print_r($_REQUEST);


    $API = new CmsApi($conf);

    //$API->setRequest($_REQUEST, $_SERVER, $_GET, $_POST);
    $API->setRequest();
    $API->authorize();

    $result = $API->processAPI();

    echo $result;




} catch (Exception $e) {
    echo json_encode(Array('error' => $e->getMessage()));
}

?>
