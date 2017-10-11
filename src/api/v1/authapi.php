<?php

// Auth API endpoint
require_once 'utils/AuthenticationApi.php';

$conf = json_decode(file_get_contents('conf/conf.json'));
$API = new AuthenticationApi($conf);
$API->execute();
