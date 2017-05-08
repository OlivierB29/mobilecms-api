<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class JwtTokenTest extends TestCase
{
    public function testBasic()
    {
        $t = new JwtToken();
        $token = $t->createTokenFromUser('test', 'test@example.com', 'guest', 'secret');
        $this->assertTrue(
          $token != null && strlen($token) > 100
        );
    }

    public function testVerifyToken()
    {
        $t = new JwtToken();
        $token = $t->createTokenFromUser('test', 'test@example.com', 'guest', 'secret');

        $this->assertTrue(
          $t->verifyToken($token, 'secret')
        );
    }

    public function testVerifyWrongSecret()
    {
        $t = new JwtToken();
        $token = $t->createTokenFromUser('test', 'test@example.com', 'guest', 'secret');

        $this->assertFalse(
          $t->verifyToken($token, 'wrongsecret')
        );
    }
}
