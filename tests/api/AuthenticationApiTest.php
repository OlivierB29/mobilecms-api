<?php

declare(strict_types=1);
namespace mobilecms\api;

use PHPUnit\Framework\TestCase;

final class AuthenticationApiTest extends TestCase
{

    protected function setUp()
    {
    }

    public function testLogin()
    {
        $path = '/api/v1/authenticate';
        $recordStr = '{ "user": "test@example.com", "password":"Sample#123456"}';

        $REQUEST = ['path' => $path];
        $headers = [];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];
        $GET = null;
        $POST = ['requestbody' => $recordStr];

        $API = new AuthenticationApi();
        $API->loadConf(realpath('tests/conf.json'));
        $API->setRootDir(realpath('tests-data'));

        $API->setRequest($REQUEST, $SERVER, $GET, $POST);
        $response = $API->processAPI();
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
        $API = new AuthenticationApi();
        $API->loadConf(realpath('tests/conf.json'));
        $API->setRootDir(realpath('tests-data'));

        $file = $API->getPrivateDirPath() . '/users/' . $email . '.json';
        if (file_exists($file)) {
            unlink($file);
        }

        $path = '/api/v1/register';

        $recordStr = '{ "name": "test register", "email": "testregister@example.com", "password":"Sample#123456", "secretQuestion": "some secret" , "secretResponse": "secret response"}';

        $REQUEST = ['path' => $path];
        $headers = [];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];
        $GET = null;
        $POST = ['requestbody' => $recordStr];

        $API->setRequest($REQUEST, $SERVER, $GET, $POST);
        $response = $API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($result != null && $result != '');

        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function testChangePassword()
    {
        $path = '/api/v1/changepassword';
        $user = 'changepassword@example.com';
        $userFile = $user . '.json';
        $API = new AuthenticationApi();
        $API->loadConf(realpath('tests/conf.json'));
        $API->setRootDir(realpath('tests-data'));
        copy($API->getPrivateDirPath() . '/save/' . $userFile, $API->getPrivateDirPath() . '/users/' . $userFile);

        $recordStr = '{ "user": "' . $user . '", "password":"Sample#123456", "newpassword":"Foobar!654321"}';

        $REQUEST = ['path' => $path];
        $headers = [];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];
        $GET = null;
        $POST = ['requestbody' => $recordStr];

        $API->setRequest($REQUEST, $SERVER, $GET, $POST);
        $response = $API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($result != null && $result != '');

        // test new password with login
        $loginRecordStr = '{ "email": "' . $user . '", "password":"Foobar!654321"}';

        $recordStr = '{ "user": "changepassword@example.com", "password":"Foobar!654321"}';

        $this->verifyChangePassword($user, $recordStr);

        // delete file
        unlink($API->getPrivateDirPath() . '/users/' . $userFile);
    }

    private function verifyChangePassword($user, $recordStr)
    {
        $path = '/api/v1/authenticate';

        $REQUEST = ['path' => $path];
        $headers = [];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];
        $GET = null;
        $POST = ['requestbody' => $recordStr];

        $API = new AuthenticationApi();
        $API->loadConf(realpath('tests/conf.json'));
        $API->setRootDir(realpath('tests-data'));

        $API->setRequest($REQUEST, $SERVER, $GET, $POST);
        $response = $API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($result != null && $result != '');

        $userObject = json_decode($result);

        $this->assertTrue($userObject->{'email'} === $user);
        $this->assertTrue(strlen($userObject->{'token'}) > 150);
    }
}
