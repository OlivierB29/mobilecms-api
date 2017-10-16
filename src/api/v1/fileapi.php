<?php

// File API endpoint
require_once 'autoload.php';


$API = new \mobilecms\api\FileApi();
$API->loadConf(realpath('conf/conf.json'));
$API->execute();
