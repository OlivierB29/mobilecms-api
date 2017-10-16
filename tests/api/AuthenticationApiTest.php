<?php

declare(strict_types=1);
namespace mobilecms\api;

use PHPUnit\Framework\TestCase;

final class AuthenticationApiTest extends ApiTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->API=new AuthenticationApi();
        $this->API->loadConf(realpath('tests/conf.json'));
        $this->API->setRootDir(realpath('tests-data')); // unit test only
    }


    public function testLogin()
    {
        $this->path = '/api/v1/authenticate';
        $recordStr = '{ "user": "test@example.com", "password":"Sample#123456"}';

        $this->REQUEST = ['path' => $this->path];

        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];

        $this->POST = ['requestbody' => $recordStr];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST);
        $response = $this->API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($result != null && $result != '');

        $userObject = json_decode($result);

        $this->assertTrue($userObject->{'email'} === 'test@example.com');
        $this->assertTrue(strlen($userObject->{'token'}) > 150);
    }

    public function testRegister()
    {
        $email = 'testregister@example.com';

        $file = $this->API->getPrivateDirPath() . '/users/' . $email . '.json';
        if (file_exists($file)) {
            unlink($file);
        }

        $this->path = '/api/v1/register';

        $recordStr = '{ "name": "test register", "email": "testregister@example.com", "password":"Sample#123456", "secretQuestion": "some secret" , "secretResponse": "secret response"}';

        $this->REQUEST = ['path' => $this->path];

        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];

        $this->POST = ['requestbody' => $recordStr];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST);
        $response = $this->API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($result != null && $result != '');

        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function testChangePassword()
    {
        $this->path = '/api/v1/changepassword';
        $user = 'changepassword@example.com';
        $userFile = $user . '.json';

        copy($this->API->getPrivateDirPath() . '/save/' . $userFile, $this->API->getPrivateDirPath() . '/users/' . $userFile);

        $recordStr = '{ "user": "' . $user . '", "password":"Sample#123456", "newpassword":"Foobar!654321"}';

        $this->REQUEST = ['path' => $this->path];

        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];

        $this->POST = ['requestbody' => $recordStr];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST);
        $response = $this->API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($result != null && $result != '');

        // test new password with login
        $loginRecordStr = '{ "email": "' . $user . '", "password":"Foobar!654321"}';

        $recordStr = '{ "user": "changepassword@example.com", "password":"Foobar!654321"}';

        $this->verifyChangePassword($user, $recordStr);

        // delete file
        unlink($this->API->getPrivateDirPath() . '/users/' . $userFile);
    }

    private function verifyChangePassword($user, $recordStr)
    {
        $this->path = '/api/v1/authenticate';

        $this->REQUEST = ['path' => $this->path];

        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];

        $this->POST = ['requestbody' => $recordStr];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST);
        $response = $this->API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($result != null && $result != '');

        $userObject = json_decode($result);

        $this->assertTrue($userObject->{'email'} === $user);
        $this->assertTrue(strlen($userObject->{'token'}) > 150);
    }
}
