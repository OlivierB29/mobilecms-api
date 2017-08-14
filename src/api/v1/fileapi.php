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
    $conf->{'enablecleaninputs'} = 'true';
    $conf->{'homedir'} = HOME;
    $conf->{'media'} = 'media';
    $conf->{'privatedir'} = PRIVATEDIR;
    $conf->{'role'} = 'editor';

    $API = new FileApi($conf);

    $API->setRequest();
    echo $API->processAPI()->getResult();
} catch (Exception $e) {
    echo json_encode([
            'error' => $e->getMessage(),
    ]);
}
