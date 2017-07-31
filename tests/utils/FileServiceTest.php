<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FileServiceTest extends TestCase
{
    private $dir = 'tests-data/fileservice';

    public function testCleanDeletedFiles()
    {
        $service = new FileService();
        $itemUri = '/calendar/1';
        $existing = json_decode('[{"title":"Deleted file","url":"\/calendar\/1\/foobar.pdf","size":24612,"mimetype":"application\/pdf"},{"title":"CUSTOM LABEL","url":"\/calendar\/1\/lorem ipsum.pdf","size":24612,"mimetype":"application\/pdf"},{"title":"tennis-178696_640.jpg","url":"\/calendar\/1\/tennis-178696_640.jpg","size":146955,"mimetype":"image\/jpeg"},{"title":"tennis-2290639_640.jpg","url":"\/calendar\/1\/tennis-2290639_640.jpg","size":106894,"mimetype":"image\/jpeg"}]');

        $response = $service->cleanDeletedFiles($this->dir, $existing);

        $expected = '[{"title":"CUSTOM LABEL","url":"\/calendar\/1\/lorem ipsum.pdf","size":24612,"mimetype":"application\/pdf"},{"title":"tennis-178696_640.jpg","url":"\/calendar\/1\/tennis-178696_640.jpg","size":146955,"mimetype":"image\/jpeg"},{"title":"tennis-2290639_640.jpg","url":"\/calendar\/1\/tennis-2290639_640.jpg","size":106894,"mimetype":"image\/jpeg"}]';
        $this->assertJsonStringEqualsJsonString($expected, json_encode($response));
    }

    public function testGetDescriptions()
    {
        $service = new FileService();
        $itemUri = '/calendar/1';
        $response = $service->getDescriptions($this->dir.$itemUri, $itemUri);

        $expected = '[{"title":"lorem ipsum.pdf","url":"\/calendar\/1\/lorem ipsum.pdf","size":24612,"mimetype":"application\/pdf"},{"title":"tennis-178696_640.jpg","url":"\/calendar\/1\/tennis-178696_640.jpg","size":146955,"mimetype":"image\/jpeg"},{"title":"tennis-2290639_640.jpg","url":"\/calendar\/1\/tennis-2290639_640.jpg","size":106894,"mimetype":"image\/jpeg"}]';
        $this->assertJsonStringEqualsJsonString($expected, json_encode($response));
        // $this->assertTrue($response);
    }

    public function testUpdateDescriptions()
    {
        $service = new FileService();
        $itemUri = '/calendar/1';
        $existing = json_decode('[{"title":"CUSTOM LABEL","url":"\/calendar\/1\/lorem ipsum.pdf","size":24612,"mimetype":"application\/pdf"},{"title":"tennis-178696_640.jpg","url":"\/calendar\/1\/tennis-178696_640.jpg","size":146955,"mimetype":"image\/jpeg"},{"title":"tennis-2290639_640.jpg","url":"\/calendar\/1\/tennis-2290639_640.jpg","size":106894,"mimetype":"image\/jpeg"}]');

        $response = $service->updateDescriptions($this->dir.$itemUri, $itemUri, $existing);

        $expected = '[{"title":"CUSTOM LABEL","url":"\/calendar\/1\/lorem ipsum.pdf","size":24612,"mimetype":"application\/pdf"},{"title":"tennis-178696_640.jpg","url":"\/calendar\/1\/tennis-178696_640.jpg","size":146955,"mimetype":"image\/jpeg"},{"title":"tennis-2290639_640.jpg","url":"\/calendar\/1\/tennis-2290639_640.jpg","size":106894,"mimetype":"image\/jpeg"}]';
        $this->assertJsonStringEqualsJsonString($expected, json_encode($response));
        // $this->assertTrue($response);
    }
}
