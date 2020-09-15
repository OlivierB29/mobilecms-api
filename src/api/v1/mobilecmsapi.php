<?php
// @codeCoverageIgnoreStart
// API endpoint
require_once 'autoload.php';

$API = null;
$uri = $_SERVER['REQUEST_URI'];

if (preg_match("/^\/" . \mobilecms\rest\RestApi::APIROOT . "\/" . \mobilecms\rest\RestApi::VERSION . "\/cmsapi/", $uri) > 0) {
    $API = new \mobilecms\api\CmsApi();
}

if (preg_match("/^\/" . \mobilecms\rest\RestApi::APIROOT . "\/" . \mobilecms\rest\RestApi::VERSION . "\/authapi/", $uri) > 0) {
    $API = new \mobilecms\api\AuthenticationApi();
}

if (preg_match("/^\/" . \mobilecms\rest\RestApi::APIROOT . "\/" . \mobilecms\rest\RestApi::VERSION . "\/adminapi/", $uri) > 0) {
    $API = new \mobilecms\api\AdminApi();
}

if (preg_match("/^\/" . \mobilecms\rest\RestApi::APIROOT . "\/" . \mobilecms\rest\RestApi::VERSION . "\/fileapi/", $uri) > 0) {
    $API = new \mobilecms\api\FileApi();
}

// run API

$API->loadConf(realpath('../conf/conf.json'));
$API->execute();
// @codeCoverageIgnoreEnd
