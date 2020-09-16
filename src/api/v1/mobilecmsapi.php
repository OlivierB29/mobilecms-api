<?php
// @codeCoverageIgnoreStart
// API endpoint
require_once 'autoload.php';

$api = null;
$uri = $_SERVER['REQUEST_URI'];

if (preg_match("/^\/" . \mobilecms\rest\RestApi::getRoot() . "\/" . \mobilecms\rest\RestApi::getVersion() . "\/cmsapi/", $uri) > 0) {
    $api = new \mobilecms\api\CmsApi();
}

if (preg_match("/^\/" . \mobilecms\rest\RestApi::getRoot() . "\/" . \mobilecms\rest\RestApi::getVersion() . "\/authapi/", $uri) > 0) {
    $api = new \mobilecms\api\AuthenticationApi();
}

if (preg_match("/^\/" . \mobilecms\rest\RestApi::getRoot() . "\/" . \mobilecms\rest\RestApi::getVersion() . "\/adminapi/", $uri) > 0) {
    $api = new \mobilecms\api\AdminApi();
}

if (preg_match("/^\/" . \mobilecms\rest\RestApi::getRoot() . "\/" . \mobilecms\rest\RestApi::getVersion() . "\/fileapi/", $uri) > 0) {
    $api = new \mobilecms\api\FileApi();
}

// run API
if (isset($api)) {
    $api->loadConf(realpath('../conf/conf.json'));
    $api->execute();
}
// @codeCoverageIgnoreEnd
