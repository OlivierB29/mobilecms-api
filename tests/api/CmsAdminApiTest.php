<?php

declare(strict_types=1);
namespace mobilecms\api;

use PHPUnit\Framework\TestCase;

final class CmsAdminApiTest extends AuthApiTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->API=new AdminApi();
        $this->API->loadConf(realpath('tests/conf.json'));
        $this->API->setRootDir(realpath('tests-data')); // unit test only
    }

    public function testTypes()
    {
        $this->setAdmin();
        $this->path = '/mobilecmsapi/v1/adminapi/content';

        $response = $this->request('GET', $this->path);



        $this->assertTrue(isset($response));

        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
    }

    public function testOptions()
    {
        $email = 'editor@example.com';
        $this->path = '/mobilecmsapi/v1/adminapi/content/users/' . $email;
        $response = $this->request('OPTIONS', $this->path);


        $this->assertTrue($response != null);
        $this->assertJsonStringEqualsJsonString('{}', $response->getEncodedResult());
        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
    }


    public function testWrongLogin()
    {
        $this->headers=['Authorization' => 'foobar'];

        $email = 'editor@example.com';
        $this->path = '/mobilecmsapi/v1/adminapi/content/users/' . $email;

        $response = $this->request('GET', $this->path);



        $this->assertEquals(401, $response->getCode());
        $this->assertTrue($response != null);
        $this->assertJsonStringEqualsJsonString('{"error":"Invalid token !"}', $response->getEncodedResult());
    }

    public function testUnauthorizedEditor()
    {
        $this->setGuest();

        $email = 'editor@example.com';
        $this->path = '/mobilecmsapi/v1/adminapi/content/users/' . $email;

        $response = $this->request('GET', $this->path);





        $this->assertEquals(403, $response->getCode());
        $this->assertTrue($response != null);
        $this->assertJsonStringEqualsJsonString('{"error":"wrong role"}', $response->getEncodedResult());
    }
    public function testUnauthorizedGuest()
    {
        $this->setGuest();

        $email = 'guest@example.com';
        $this->path = '/mobilecmsapi/v1/adminapi/content/users/' . $email;

        $response = $this->request('GET', $this->path);





        $this->assertEquals(403, $response->getCode());
        $this->assertTrue($response != null);
        $this->assertJsonStringEqualsJsonString('{"error":"wrong role"}', $response->getEncodedResult());
    }

    public function testGet()
    {
        $this->setAdmin();
        $email = 'editor@example.com';
        $this->path = '/mobilecmsapi/v1/adminapi/content/users/' . $email;

        $response = $this->request('GET', $this->path);




        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($response != null);

        $userObject = $response->getResult();
        $this->assertTrue($userObject->{'name'} === 'editor@example.com');
        $this->assertTrue($userObject->{'email'} === 'editor@example.com');
        $this->assertTrue($userObject->{'role'} === 'editor');
        $this->assertTrue(!isset($userObject->{'password'}));
    }

    public function testGetAll()
    {
        $this->setAdmin();
        $email = 'editor@example.com';
        $this->path = '/mobilecmsapi/v1/adminapi/content/users';

        $response = $this->request('GET', $this->path);




        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($response != null);
    }


    public function testCreatePost()
    {
        $this->setAdmin();
        $email = 'newuser@example.com';
        $this->path = '/mobilecmsapi/v1/adminapi/content/users/';
        $file = $this->API->getPrivateDirPath() . '/users/' . $email . '.json';

        $recordStr = '{ "name": "test role", "email": "' . $email . '", "role":"editor", "password":"Something1234567890"}';
        $this->POST = ['requestbody' => $recordStr];

        $response = $this->request('POST', $this->path);



        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($response != null);
        $this->assertTrue(file_exists($file));

        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function testResetPassword()
    {
        $userdir = $this->API->getPrivateDirPath() . '/users/';
        $email = 'modifypassword@example.com';
        $file = $userdir . '/' . $email . '.json';
        copy($userdir . '/' . $email . '.backup.json', $file);

        $this->setAdmin();

        $this->path = '/mobilecmsapi/v1/adminapi/content/users/' . $email;

        $recordStr = '{ "name": "test", "email": "' . $email . '", "role":"editor", "newpassword":"Something1234567890"}';
        $this->POST = ['requestbody' => $recordStr];

        $response = $this->request('POST', $this->path);



        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($response != null);
        $this->assertTrue(file_exists($file));

        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function testDelete()
    {
        $this->setAdmin();
        $email = 'delete@example.com';
        $this->path = '/mobilecmsapi/v1/adminapi/content/users/' . $email;
        $file = $this->API->getPrivateDirPath() . '/users/' . $email . '.json';

        $this->assertTrue(copy($this->API->getPrivateDirPath() . '/save/' . $email . '.json', $file));


        $response = $this->request('DELETE', $this->path);



        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($response != null);
        $this->assertTrue(!file_exists($file));
    }


    public function testIndex()
    {
        $this->setAdmin();
        $this->path = '/mobilecmsapi/v1/adminapi/index/users' ;



        $response = $this->request('GET', $this->path);

        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($response != null);
    }
    public function testRebuildUserIndex()
    {
        $this->setAdmin();
        $this->path = '/mobilecmsapi/v1/adminapi/index/users' ;



        $response = $this->request('POST', $this->path);



        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($response != null);
    }


    public function testUpdate()
    {
        $this->setAdmin();
        $email = 'role@example.com';
        $this->path = '/mobilecmsapi/v1/adminapi/content/users/' . $email;




        $file = $this->API->getPrivateDirPath() . '/users/' . $email . '.json';
        $this->assertTrue(copy($this->API->getPrivateDirPath() . '/save/' . $email . '.json', $file));




        $recordStr = '{ "name": "test role", "email": "' . $email . '", "role":"editor"}';
        $this->POST = ['requestbody' => $recordStr];

        $response = $this->request('POST', $this->path);



        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($response != null);
        $this->assertTrue(file_exists($file));

        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function testGetMetadata()
    {
        $this->setAdmin();
        $this->path = '/mobilecmsapi/v1/adminapi/metadata/users';


        $response = $this->request('GET', $this->path);


        $this->printError($response);
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($response != null);
        $index_data = file_get_contents($this->API->getPrivateDirPath() . '/users/index/metadata.json');

        $this->assertJsonStringEqualsJsonString($index_data, $response->getEncodedResult());
    }
}
