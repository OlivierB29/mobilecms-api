<?php
// @codeCoverageIgnoreStart
// Auth API endpoint
require_once 'autoload.php';


$API = new \mobilecms\api\AuthenticationApi();
$API->loadConf(realpath('conf/conf.json'));
$API->execute();
// @codeCoverageIgnoreEnd
