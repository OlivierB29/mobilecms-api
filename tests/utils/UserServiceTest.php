<?php

declare(strict_types=1);
namespace mobilecms\utils;

use PHPUnit\Framework\TestCase;

final class UserServiceTest extends TestCase
{
    private $service;

    protected function setUp()
    {
        $this->service = new UserService('tests-data/userservice');
    }


    public function testCanRead()
    {
        $this->assertTrue(
          $this->service->getJsonUser('test@example.com') !== null
        );
    }

    public function testUpdateUser()
    {
        $this->assertTrue(
          $this->service->updateUser('updateuser@example.com', 'updated', 'pass', 'salt', 'admin')
        );
    }



    public function testGetJsonUserFileEmptyEmail()
    {
        $this->expectException(\Exception::class);
        $this->service->getJsonUserFile('');
    }
    public function testGetJsonUserFileEmptyDatabase()
    {
        $this->expectException(\Exception::class);
        $service = new UserService('');
        $service->getJsonUserFile('foo');
    }
}
