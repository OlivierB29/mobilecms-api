<?php

declare(strict_types=1);
namespace mobilecms\api;

use PHPUnit\Framework\TestCase;

final class CmsAdminApiTest extends AuthApiTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->API=new AdminApi();
        $this->API->loadConf(realpath('tests/conf.json'));
        $this->API->setRootDir(realpath('tests-data')); // unit test only
    }

    public function testUpdate()
    {
        $email = 'role@example.com';
        $this->path = '/adminapi/v1/content/users/' . $email;




        $file = $this->API->getPrivateDirPath() . '/users/' . $email . '.json';
        $this->assertTrue(copy($this->API->getPrivateDirPath() . '/save/' . $email . '.json', $file));



        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];


        $recordStr = '{ "name": "test role", "email": "' . $email . '", "role":"editor"}';
        $this->POST = ['requestbody' => $recordStr];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);
        $response = $this->API->processAPI();

        $result = $response->getResult();

        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($result != null && $result != '');
        $this->assertTrue(file_exists($file));

        if (file_exists($file)) {
            unlink($file);
        }
    }
}
