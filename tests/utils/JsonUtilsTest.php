<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;


final class JsonUtilsTest extends TestCase
{
    public function testCanRead()
    {
        $this->assertTrue(
          JsonUtils::readJsonFile('tests-data/jsonutils/test.json') !== null
        );
    }


    public function testCanBeUsedAsString()
    {
        $this->assertJsonStringEqualsJsonString(
           '{}',
            json_encode(JsonUtils::readJsonFile('tests-data/jsonutils/test.json'))
        );
    }
}
