<?php

declare(strict_types=1);
namespace mobilecms\utils;

use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    private $service;

    protected function setUp()
    {
        $this->service = new AuthService('tests-data/userservice');
    }


    public function testCreateUser()
    {
        $mail = 'testcreate@example.com';
        $file = 'tests-data/userservice/' . $mail . '.json';
        if (file_exists($file)) {
            unlink($file);
        }
        $password = 'Sample#123456';

        $createresult = $this->service->createUser($mail, $mail, $password, 'create');
        $this->assertTrue($createresult === null);
        unlink($file);
    }



    public function testCreateUserEmptyEmail()
    {
        $mail = '';
        $password = 'foo';
        $createresult = $this->service->createUser($mail, $mail, $password, 'create');
        $this->assertTrue($createresult !== null);
        $this->assertTrue(strpos($createresult, 'EmptyEmail') !== false);
    }

    public function testCreateUserInvalidEmail()
    {
        $mail = 'foobar';
        $password = 'foo';
        $createresult = $this->service->createUser($mail, $mail, $password, 'create');
        $this->assertTrue($createresult !== null);
        $this->assertTrue(strpos($createresult, 'InvalidEmail') !== false);
    }

    public function testCreateUserAlreadyExists()
    {
        $mail = 'testcreate@example.com';
        $file = 'tests-data/userservice/' . $mail . '.json';
        if (file_exists($file)) {
            unlink($file);
        }
        $password = 'Sample#123456';

        $this->service->createUser($mail, $mail, $password, 'create');
        $createresult = $this->service->createUser($mail, $mail, $password, 'create');
        $this->assertTrue($createresult !== null);
        unlink($file);
    }


    public function testCreateUserEmptyPassword()
    {
        $mail = 'testcreate@example.com';
        $password = '';
        $createresult = $this->service->createUser($mail, $mail, $password, 'create');
        $this->assertTrue($createresult !== null);
        $this->assertTrue(strpos($createresult, 'EmptyPassword') !== false);
    }
    public function testCreateUserEmptyUsername()
    {
        $mail = 'testcreate@example.com';
        $password = 'foo';
        $createresult = $this->service->createUser('', $mail, $password, 'create');
        $this->assertTrue($createresult !== null);
        $this->assertTrue(strpos($createresult, 'InvalidUser') !== false);
    }



    public function testLoginOk()
    {
        $result = $this->service->login('test@example.com', 'Sample#123456');

        $this->assertTrue('' === $result);
    }

    public function testGetToken()
    {
        $result = $this->service->getToken('test@example.com', 'Sample#123456');
        $this->assertTrue($result->getCode() === 200);
        $this->assertTrue(null != $result->getResult());

        $user = json_decode($result->getResult());

        $this->assertTrue($user->{'name'} === 'test@example.com');
        $this->assertTrue($user->{'email'} === 'test@example.com');
        $this->assertTrue(strlen($user->{'token'}) > 100);
    }

    public function testEmptyToken()
    {
        $this->expectException(\Exception::class);
        $result = $this->service->verifyToken(null, 'editor');
    }

    public function testVerifyToken()
    {
        $getTokenResponse = $this->service->getToken('test@example.com', 'Sample#123456');

        $user = json_decode($getTokenResponse->getResult());

        $result = $this->service->verifyToken($user->{'token'}, 'editor');

        $this->assertTrue($result->getCode() === 200);
    }

    public function testVerifyWrongToken()
    {
        $getTokenResponse = $this->service->getToken('test@example.com', 'Sample#123456');

        $user = json_decode($getTokenResponse->getResult());

        $result = $this->service->verifyToken($user->{'token'} . 'FOOBAR', 'editor');

        $this->assertTrue($result->getCode() === 401);
    }

    public function testInsufficentEditorRole()
    {
        $getTokenResponse = $this->service->getToken('guest@example.com', 'Sample#123456');

        $user = json_decode($getTokenResponse->getResult());

        $result = $this->service->verifyToken($user->{'token'}, 'editor');

        $this->assertTrue($result->getCode() === 403);
    }

    public function testInsufficentAdminRole()
    {
        $getTokenResponse = $this->service->getToken('test@example.com', 'Sample#123456');

        $user = json_decode($getTokenResponse->getResult());

        $result = $this->service->verifyToken($user->{'token'}, 'admin');

        $this->assertTrue($result->getCode() === 403);
    }

    public function testVerifyEditorByAdmin()
    {
        $getTokenResponse = $this->service->getToken('admin@example.com', 'Sample#123456');

        $user = json_decode($getTokenResponse->getResult());

        $result = $this->service->verifyToken($user->{'token'}, 'editor');

        $this->assertTrue($result->getCode() === 200);
    }

    public function testVerifyAdmin()
    {
        $getTokenResponse = $this->service->getToken('admin@example.com', 'Sample#123456');

        $user = json_decode($getTokenResponse->getResult());

        $result = $this->service->verifyToken($user->{'token'}, 'admin');

        $this->assertTrue($result->getCode() === 200);
    }

    public function testTokenKo()
    {
        $result = $this->service->getToken('test@example.com', 'Sample#1234567');
        $this->assertTrue($result->getCode() === 401);
        $this->assertTrue($result->getResult() === '{}');
    }

    public function testWrongLogin1()
    {
        $result = $this->service->login('test@example.com', 'wrongpass');
        $this->assertTrue(
          $result !== null
        );
    }

    public function testWrongLogin2()
    {
        $result = $this->service->login('test@example.com', 'Sample#12345');
        $this->assertTrue(
          $result !== null
        );
    }



    public function testResetPasswordWrongUser()
    {
        $this->expectException(\Exception::class);
        $this->assertTrue(
          $this->service->resetPassword('FOOBAR@example.com', 'foo')
        );
    }






    public function testModifyPassword()
    {
        $userdir = 'tests-data/userservice';
        $email = 'modifypasssword@example.com';

        copy($userdir . '/' . $email . '.backup.json', $userdir . '/' . $email . '.json');

        $oldPassword = 'Sample#123456';

        $service = new AuthService($userdir);

        //change password
        $newPassword = 'somethingnew';
        $createresult = $this->service->changePassword($email, $oldPassword, $newPassword);

        $this->assertTrue($createresult->getCode() === 200);

        //login
        $result = $this->service->login($email, $newPassword);
        $this->assertTrue('' === $result);
    }

    public function testModifyWithWrongPassword()
    {
        $userdir = 'tests-data/userservice';
        $email = 'modifypasssword@example.com';

        copy($userdir . '/' . $email . '.backup.json', $userdir . '/' . $email . '.json');

        $oldPassword = 'foo';

        $service = new AuthService($userdir);

        //change password
        $newPassword = 'somethingnew';
        $createresult = $this->service->changePassword($email, $oldPassword, $newPassword);

        $this->assertTrue($createresult->getCode() === 401);

    }
}
