<?php

declare(strict_types=1);
require_once 'conf.php';

use PHPUnit\Framework\TestCase;

final class AuthenticationApiTest extends TestCase
{
    private $conf;

    protected function setUp()
    {
        $this->conf = json_decode('{}');
        $this->conf->{'enableheaders'} = 'false';
        $this->conf->{'enableapikey'} = 'false';
        $this->conf->{'enablecleaninputs'} = 'false';
        $this->conf->{'privatedir'} = HOME.'/tests-data/private';
        $this->conf->{'apikeyfile'} = HOME.'/tests-data/private/apikeys/key1.json';
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

        $API = new AuthenticationApi($this->conf);

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
        $file = $this->conf->{'privatedir'}.'/users/'.$email.'.json';
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

        $API = new AuthenticationApi($this->conf);

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
        $userFile = $user.'.json';
        copy($this->conf->{'privatedir'}.'/save/'.$userFile, $this->conf->{'privatedir'}.'/users/'.$userFile);

        $recordStr = '{ "user": "'.$user.'", "password":"Sample#123456", "newpassword":"Foobar!654321"}';

        $REQUEST = ['path' => $path];
        $headers = [];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];
        $GET = null;
        $POST = ['requestbody' => $recordStr];

        $API = new AuthenticationApi($this->conf);

        $API->setRequest($REQUEST, $SERVER, $GET, $POST);
        $response = $API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($result != null && $result != '');

        // test new password with login
        $loginRecordStr = '{ "email": "'.$user.'", "password":"Foobar!654321"}';

        $recordStr = '{ "user": "changepassword@example.com", "password":"Foobar!654321"}';

        $this->verifyChangePassword($user, $recordStr);

        // delete file
        unlink($this->conf->{'privatedir'}.'/users/'.$userFile);
    }

    private function verifyChangePassword($user, $recordStr)
    {
        $path = '/api/v1/authenticate';

        $REQUEST = ['path' => $path];
        $headers = [];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];
        $GET = null;
        $POST = ['requestbody' => $recordStr];

        $API = new AuthenticationApi($this->conf);

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
