<?php

// CMS API endpoint
require_once 'autoload.php';

$API = new \mobilecms\api\CmsApi();
$API->loadConf(realpath('conf/conf.json'));
$API->execute();
