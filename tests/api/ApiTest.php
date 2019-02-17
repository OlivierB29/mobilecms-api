<?php
namespace mobilecms\api;

use PHPUnit\Framework\TestCase;

// reminder : PHPUnit autoloader seems to import files with an alphabetic order.

abstract class ApiTest extends TestCase
{
    protected $path='';
    protected $headers=[];
    protected $REQUEST=[];
    protected $SERVER=[];
    protected $GET=[];
    protected $POST=[];

    protected $memory1 = 0;
    protected $memory2 = 0;

    protected $API;


    protected function setUp(): void
    {
        $this->path='';
        $this->headers=[];
        $this->REQUEST=[];
        $this->SERVER=[];
        $this->GET=[];
        $this->POST=[];
    }

    protected function memory()
    {
        $this->memory1 = $this->memory2;

        $this->memory2 = memory_get_usage();

        return $this->memory2 - $this->memory1;
    }

    protected function printError(\mobilecms\utils\Response $response)
    {
        if ($response->getCode() != 200) {
            echo 'ERROR ' . $response->getEncodedResult();
        }
    }

    protected function request($verb, $path): \mobilecms\utils\Response
    {
        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => $verb, 'HTTP_ORIGIN' => 'foobar'];
        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);
        return $this->API->processAPI();
    }

    protected function authrequest($verb, $path): \mobilecms\utils\Response
    {
        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => $verb, 'HTTP_ORIGIN' => 'foobar'];
        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);
        $this->API->authorize($this->headers, $this->SERVER);
        return $this->API->processAPI();
    }
}
