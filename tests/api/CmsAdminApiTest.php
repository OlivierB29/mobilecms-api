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



    public function testWrongLogin()
    {
        $this->headers=['Authorization' => 'foobar'];

        $email = 'editor@example.com';
        $this->path = '/adminapi/v1/content/users/' . $email;

        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);
        $response = $this->API->processAPI();

        $result = $response->getResult();


        $this->assertEquals(401, $response->getCode());
        $this->assertTrue($result != null && $result != '');
        $this->assertJsonStringEqualsJsonString('{"error":"Invalid token !"}', $result);
    }

    public function testUnauthorizedGuest()
    {
        $this->setGuest();

        $email = 'editor@example.com';
        $this->path = '/adminapi/v1/content/users/' . $email;

        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);
        $response = $this->API->processAPI();

        $result = $response->getResult();


        $this->assertEquals(403, $response->getCode());
        $this->assertTrue($result != null && $result != '');
        $this->assertJsonStringEqualsJsonString('{"error":"wrong role"}', $result);
    }

    public function testGet()
    {
        $this->setAdmin();
        $email = 'editor@example.com';
        $this->path = '/adminapi/v1/content/users/' . $email;

        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);
        $response = $this->API->processAPI();

        $result = $response->getResult();

        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($result != null && $result != '');

        $userObject = json_decode($result);
        $this->assertTrue($userObject->{'name'} === 'editor@example.com');
        $this->assertTrue($userObject->{'email'} === 'editor@example.com');
        $this->assertTrue($userObject->{'role'} === 'editor');
        $this->assertTrue(!isset($userObject->{'password'}));
    }


    public function testCreate()
    {
        $this->setAdmin();
        $email = 'newuser@example.com';
        $this->path = '/adminapi/v1/content/users/';
        $file = $this->API->getPrivateDirPath() . '/users/' . $email . '.json';

        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];


        $recordStr = '{ "name": "test role", "email": "' . $email . '", "role":"editor", "password":"Something1234567890"}';
        $this->POST = ['requestbody' => $recordStr];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);
        $response = $this->API->processAPI();

        $result = $response->getResult();
        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($result != null && $result != '');
        $this->assertTrue(file_exists($file));

        if (file_exists($file)) {
            unlink($file);
        }
    }



    public function testDelete()
    {
        $this->setAdmin();
        $email = 'delete@example.com';
        $this->path = '/adminapi/v1/content/users/' . $email;
        $file = $this->API->getPrivateDirPath() . '/users/' . $email . '.json';

        $this->assertTrue(copy($this->API->getPrivateDirPath() . '/save/' . $email . '.json', $file));


        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'DELETE', 'HTTP_ORIGIN' => 'foobar'];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);
        $response = $this->API->processAPI();

        $result = $response->getResult();
        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($result != null && $result != '');
        $this->assertTrue(!file_exists($file));
    }


    public function testIndex()
    {
        $this->setAdmin();
        $this->path = '/adminapi/v1/index/users' ;



        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);
        $response = $this->API->processAPI();
        $this->assertEquals(200, $response->getCode());
        $result = $response->getResult();
        $this->assertTrue($result != null && $result != '');
    }
    public function testUpdate()
    {
        $this->setAdmin();
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

        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($result != null && $result != '');
        $this->assertTrue(file_exists($file));

        if (file_exists($file)) {
            unlink($file);
        }
    }
}
