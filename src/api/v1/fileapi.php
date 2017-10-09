<?php

require_once 'utils/FileApi.php';

$conf = json_decode(file_get_contents('conf/conf.json'));
$API = new FileApi($conf);

$API->execute();
