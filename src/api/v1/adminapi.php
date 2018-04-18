<?php
// @codeCoverageIgnoreStart
// Admin API endpoint
require_once 'autoload.php';


$API = new \mobilecms\api\AdminApi();
$API->loadConf(realpath('conf/conf.json'));
$API->execute();
// @codeCoverageIgnoreEnd
