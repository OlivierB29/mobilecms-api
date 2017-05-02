<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    public function testCanRead()
    {
        $service = new UserService('tests-data/userservice');
        $this->assertTrue(
          $service->getJsonUser('test@example.com') !== null
        );
    }

    public function testLoginOk()
    {

        $service = new UserService('tests-data/userservice');
        $result = $service->login('test@example.com', 'Sample#123456');

        $this->assertTrue('' === $result);
    }

    public function testGetToken()
    {

        $service = new UserService('tests-data/userservice');
        $result = $service->getToken('test@example.com', 'Sample#123456');
        $this->assertTrue($result->getCode() === 200);
        $this->assertTrue(null != $result->getResult() );

        $user = json_decode($result->getResult());

        $this->assertTrue($user->{'username'} === 'test@example.com' );
        $this->assertTrue($user->{'email'} === 'test@example.com' );
        $this->assertTrue(strlen($user->{'token'}) > 100 );


    }

    public function testVerifyToken()
    {
        $service = new UserService('tests-data/userservice');
        $getTokenResponse = $service->getToken('test@example.com', 'Sample#123456');

        $user = json_decode($getTokenResponse->getResult());

        $result = $service->verifyToken($user->{'token'});

        $this->assertTrue($result->getCode() === 200);
    }

    public function testTokenKo()
    {
        $service = new UserService('tests-data/userservice');
        $result = $service->getToken('test@example.com', 'Sample#1234567');
        $this->assertTrue($result->getCode() === 401);
        $this->assertTrue($result->getResult() === '{}');
    }

    public function testCreatePasswordAndLogin()
    {
        $service = new UserService('tests-data/temp');
        $mail = 'test' . time() . '@example.com';
        $password = 'Sample#123456';

        $createresult = $service->createUserWithSecret($mail, $mail, $password, 'some secret', 'secret response') ;
        $result = $service->login($mail, $password);

        $this->assertTrue($result === '');
    }

    public function testWrongLogin1()
    {
        $service = new UserService('tests-data/userservice');
        $result = $service->login('test@example.com', 'wrongpass');
        $this->assertTrue(
          $result !== null
        );
    }

    public function testWrongLogin2()
    {
        $service = new UserService('tests-data/userservice');
        $result = $service->login('test@example.com', 'Sample#12345');
        $this->assertTrue(
          $result !== null
        );
    }

    public function testUpdateUser()
    {
        $service = new UserService('tests-data/userservice');
        $this->assertTrue(
          $service->updateUser('updateuser@example.com', 'updated', 'pass', 'salt', 'admin')
        );
    }
/*
    public function testCreateUser() {



       $service = new UserService('tests-data/temp');
       $mail = 'test' . rand(0, 999999) . '@example.com';

       echo 'creating user' . $mail;
       $result = $service->createUserWithSecret($mail, $mail, 'Sample#123456', 'some secret', 'secret response') ;
        $this->assertTrue( $result === null
        );
    }
*/
}
