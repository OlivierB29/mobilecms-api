<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class JsonUtilsTest extends TestCase
{
    public function testCanRead()
    {
        $this->assertJsonStringEqualsJsonString(
           '{}',
            json_encode(JsonUtils::readJsonFile('tests-data/jsonutils/mini.json'))
        );
    }

    public function testGetByKey()
    {
        $data = JsonUtils::readJsonFile('tests-data/jsonutils/test.json');

        $item = JsonUtils::getByKey($data, 'id', '1');

        $this->assertJsonStringEqualsJsonString(
        '{"id":"1", "foo":"bar"}',
        json_encode($item)

      );
    }

    public function testUpdateByKey()
    {
        $data = JsonUtils::readJsonFile('tests-data/jsonutils/test.json');

        $item = JsonUtils::getByKey($data, 'id', '1');

        $item->{'foo'} = 'pub';

        $this->assertJsonStringEqualsJsonString(
        '{"id":"1", "foo":"pub"}',
        json_encode($item)
      );

        $this->assertJsonStringEqualsJsonString(
      '[{"id":"1", "foo":"pub"},{"id":"2", "foo":"bar"}]',
      json_encode($data)
    );
    }

    public function testCopy()
    {
        $data = JsonUtils::readJsonFile('tests-data/jsonutils/test.json');

        $item = JsonUtils::getByKey($data, 'id', '1');

        $this->assertJsonStringEqualsJsonString(
        '{"id":"1", "foo":"bar"}',
        json_encode($item)
      );

        $newItem = json_decode('{"id":"1", "foo":"pub"}');

        $this->assertTrue(
        $newItem != null
      );

        JsonUtils::copy($newItem, $item);

        $this->assertJsonStringEqualsJsonString(
        '{"id":"1", "foo":"pub"}',
        json_encode($item)
      );

        $this->assertJsonStringEqualsJsonString(
      '[{"id":"1", "foo":"pub"},{"id":"2", "foo":"bar"}]',
      json_encode($data)
    );
    }

    public function testReplace()
    {
        $data = JsonUtils::readJsonFile('tests-data/jsonutils/test.json');

        $item = JsonUtils::getByKey($data, 'id', '1');

        $this->assertJsonStringEqualsJsonString(
        '{"id":"1", "foo":"bar"}',
        json_encode($item)
      );

        $newItem = json_decode('{"id":"1", "foo":"pub" , "hello":"world"}');

        $this->assertTrue(
        $newItem != null
      );

        JsonUtils::replace($newItem, $item);

        $this->assertJsonStringEqualsJsonString(
        '{"id":"1", "foo":"pub", "hello":"world"}',
        json_encode($item)
      );

        $this->assertJsonStringEqualsJsonString(
      '[{"id":"1", "foo":"pub", "hello":"world"},{"id":"2", "foo":"bar"}]',
      json_encode($data)
    );
    }

    public function testPutExistingItem()
    {
        $data = JsonUtils::readJsonFile('tests-data/jsonutils/test.json');

        $item = json_decode('{"id":"1", "foo":"pub"}');
        JsonUtils::put($data, 'id', $item);

        $this->assertJsonStringEqualsJsonString(
      '[{"id":"1", "foo":"pub"},{"id":"2", "foo":"bar"}]',
      json_encode($data)
    );
    }

    public function testPutNewItem()
    {
        $data = JsonUtils::readJsonFile('tests-data/jsonutils/test.json');

        $item = json_decode('{"id":"100", "foo":"bar"}');
        $data = JsonUtils::put($data, 'id', $item);

        $this->assertJsonStringEqualsJsonString(
      '[{"id":"1", "foo":"bar"},{"id":"2", "foo":"bar"},{"id":"100", "foo":"bar"}]',
      json_encode($data)
    );
    }
}
