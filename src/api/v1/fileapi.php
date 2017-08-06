<?php

include 'conf.php';
include 'utils/FileApi.php';

// cross domain !
if (null !== ALLOW_CROSS_DOMAIN && ALLOW_CROSS_DOMAIN === 'true') {
    header('Access-Control-Allow-Origin: *');
}

try {
    $conf = json_decode('{"enableheaders" : "","enableapikey" : "true", "publicdir":"", "privatedir":"" , "apikeyfile" : "" }');
    $conf->{'enableheaders'} = 'true';
    $conf->{'enableapikey'} = 'false';
    $conf->{'homedir'} = HOME;
    $conf->{'media'} = 'media';
    $conf->{'privatedir'} = PRIVATEDIR;

    $API = new FileApi($conf);

    $API->setRequest();
    echo $API->processAPI();
} catch (Exception $e) {
    echo json_encode([
            'error' => $e->getMessage(),
    ]);
}
