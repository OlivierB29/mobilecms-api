<?php

declare(strict_types=1);
namespace mobilecms\utils;

use PHPUnit\Framework\TestCase;

final class JsonUtilsTest extends TestCase
{
    public function testCanRead()
    {
        $this->assertJsonStringEqualsJsonString(
            '{}',
            json_encode(\mobilecms\utils\JsonUtils::readJsonFile('tests-data/jsonutils/mini.json'))
        );
    }
    public function testNewDirectory()
    {
        \mobilecms\utils\JsonUtils::writeJsonFile('tests-data/newpath/test.json', \json_decode('{}'));
        $this->assertTrue(file_exists('tests-data/newpath/test.json'));
    }

    public function testWriteException()
    {
        $this->expectException(\Exception::class);
        \mobilecms\utils\JsonUtils::writeJsonFile('/root/test.json', \json_decode('{}'));
    }

    public function testGetByKey()
    {
        $data = \mobilecms\utils\JsonUtils::readJsonFile('tests-data/jsonutils/test.json');

        $item = \mobilecms\utils\JsonUtils::getByKey($data, 'id', '1');

        $this->assertJsonStringEqualsJsonString(
            '{"id":"1", "foo":"bar"}',
            json_encode($item)
        );
    }

    public function testUpdateByKey()
    {
        $data = \mobilecms\utils\JsonUtils::readJsonFile('tests-data/jsonutils/test.json');

        $item = \mobilecms\utils\JsonUtils::getByKey($data, 'id', '1');

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
        $data = \mobilecms\utils\JsonUtils::readJsonFile('tests-data/jsonutils/test.json');

        $item = \mobilecms\utils\JsonUtils::getByKey($data, 'id', '1');

        $this->assertJsonStringEqualsJsonString(
            '{"id":"1", "foo":"bar"}',
            json_encode($item)
        );

        $newItem = json_decode('{"id":"1", "foo":"pub"}');

        $this->assertTrue(
            $newItem != null
        );

        \mobilecms\utils\JsonUtils::copy($newItem, $item);

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
        $data = \mobilecms\utils\JsonUtils::readJsonFile('tests-data/jsonutils/test.json');

        $item = \mobilecms\utils\JsonUtils::getByKey($data, 'id', '1');

        $this->assertJsonStringEqualsJsonString(
            '{"id":"1", "foo":"bar"}',
            json_encode($item)
        );

        $newItem = json_decode('{"id":"1", "foo":"pub" , "hello":"world"}');

        $this->assertTrue(
            $newItem != null
        );

        \mobilecms\utils\JsonUtils::replace($newItem, $item);

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
        $data = \mobilecms\utils\JsonUtils::readJsonFile('tests-data/jsonutils/test.json');

        $item = json_decode('{"id":"1", "foo":"pub"}');
        $data = \mobilecms\utils\JsonUtils::put($data, 'id', $item);

        $this->assertJsonStringEqualsJsonString(
            '[{"id":"2", "foo":"bar"},{"id":"1", "foo":"pub"}]',
            json_encode($data)
        );
    }

    public function testPutNewItem()
    {
        $data = \mobilecms\utils\JsonUtils::readJsonFile('tests-data/jsonutils/test.json');

        $item = json_decode('{"id":"100", "foo":"bar"}');
        $data = \mobilecms\utils\JsonUtils::put($data, 'id', $item);

        $this->assertJsonStringEqualsJsonString(
            '[{"id":"1", "foo":"bar"},{"id":"2", "foo":"bar"},{"id":"100", "foo":"bar"}]',
            json_encode($data)
        );
    }
}
