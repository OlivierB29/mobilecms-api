<?php

// CMS API endpoint
require_once 'autoload.php';

$conf = json_decode(file_get_contents('conf/conf.json'));

$API = new CmsApi($conf);
$API->execute();
