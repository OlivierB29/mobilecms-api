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


    protected function setUp()
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
}
