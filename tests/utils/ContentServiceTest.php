<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ContentServiceTest extends TestCase
{
    private $dir = 'tests-data/public';

    private $indexfile = 'calendar/index/index.json';

    public function testGetAll()
    {
        $service = new ContentService($this->dir);
        $response = $service->getAll($this->indexfile);

        $this->assertEquals(200, $response->getCode());

        $this->assertTrue(
          strstr($response->getResult(), '"id":"1"') != ''
        );

        $this->assertTrue(
          strstr($response->getResult(), '"id":"2"') != ''
        );
    }

    public function testGetItemFromList()
    {
        $service = new ContentService($this->dir);
        $response = $service->get($this->indexfile, 'id', '1');

        $this->assertEquals(200, $response->getCode());

        $this->assertJsonStringEqualsJsonString(
        json_encode(json_decode('    { "id": "1","date": "201509", "activity": "activitya", "title": "some seminar of activity A"}')),
        $response->getResult()
      );
    }

    public function testPost()
    {
        $recordStr = '{"id":"10","date":"20150901","activity":"activitya","title":"some seminar of activity A","organization":"Some org","description":"<some infos","url":"","location":"","startdate":"","enddate":"","updated":"","updatedby":""}';
        $service = new ContentService($this->dir);
        $response = $service->post('calendar', 'id', $recordStr);

        $file = $this->dir . '/calendar/10.json';

        $this->assertEquals(200, $response->getCode());

        $this->assertJsonStringEqualsJsonFile(
            $file, $recordStr
        );
    }

    public function testPublish()
    {
        $service = new ContentService($this->dir);
        $response = $service->publishById('calendar', 'id', '10');

        if ($response->getCode() !== 200) {
            echo $response->getResult();
            echo $response->getMessage();
        }

        $this->assertEquals(200, $response->getCode());
    }

    public function testRebuildIndex()
    {
        $service = new ContentService($this->dir);
        $response = $service->rebuildIndex('calendar', 'id');

        if ($response->getCode() !== 200) {
            echo  '!!!!!!!!!!!' . $response->getResult();
            echo '!!!!!!!!!!!' . $response->getMessage();
        }

        $this->assertEquals(200, $response->getCode());
    }
}
