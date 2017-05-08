<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ApiKeyTest extends TestCase
{
    public function testOk()
    {
        $apikey = new ApiKey();

        $this->assertTrue(
          $apikey->verifyKey('tests-data/apikey/key1.json', '123', 'foobar')
        );
    }

    public function testWrongKey()
    {
        $apikey = new ApiKey();

        $this->assertFalse(
          $apikey->verifyKey('tests-data/apikey/key1.json', '1234', 'foobar')
        );
    }

    public function testWrongOrigin()
    {
        $apikey = new ApiKey();

        $this->assertFalse(
          $apikey->verifyKey('tests-data/apikey/key1.json', '123', 'other')
        );
    }
}
