<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FileServiceTest extends TestCase
{
    private $dir = 'tests-data/fileservice';


    public function testGetDescriptions()
    {
        $service = new FileService();
        $itemUri = '/calendar/1';
        $response = $service->getDescriptions($this->dir.$itemUri);

        $expected = '[{"title":"lorem ipsum.pdf","url":"lorem ipsum.pdf","size":24612,"mimetype":"application\/pdf"},{"title":"tennis-178696_640.jpg","url":"tennis-178696_640.jpg","size":146955,"mimetype":"image\/jpeg"},{"title":"tennis-2290639_640.jpg","url":"tennis-2290639_640.jpg","size":106894,"mimetype":"image\/jpeg"}]';
        $this->assertJsonStringEqualsJsonString($expected, json_encode($response));
        // $this->assertTrue($response);
    }

    public function testUpdateDescriptions()
    {
        $service = new FileService();
        $itemUri = '/calendar/1';
        $existing = json_decode('[{"title":"CUSTOM LABEL","url":"lorem ipsum.pdf","size":24612,"mimetype":"application\/pdf"},{"title":"tennis-178696_640.jpg","url":"tennis-178696_640.jpg","size":146955,"mimetype":"image\/jpeg"},{"title":"tennis-2290639_640.jpg","url":"tennis-2290639_640.jpg","size":106894,"mimetype":"image\/jpeg"}]');

        $response = $service->updateDescriptions($this->dir.$itemUri, $existing);

        $expected = '[{"title":"CUSTOM LABEL","url":"lorem ipsum.pdf","size":24612,"mimetype":"application\/pdf"},{"title":"tennis-178696_640.jpg","url":"tennis-178696_640.jpg","size":146955,"mimetype":"image\/jpeg"},{"title":"tennis-2290639_640.jpg","url":"tennis-2290639_640.jpg","size":106894,"mimetype":"image\/jpeg"}]';
        $this->assertJsonStringEqualsJsonString($expected, json_encode($response));
        // $this->assertTrue($response);
    }
}
