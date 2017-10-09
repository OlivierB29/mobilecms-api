<?php

include 'utils/AdminApi.php';

$conf = json_decode(file_get_contents('conf/conf.json'));

$API = new AdminApi($conf);
$API->execute();
