<?php

declare(strict_types=1);
namespace mobilecms\api;

final class CmsApiTest extends AuthApiTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->API=new CmsApi();
        $this->API->loadConf(realpath('tests/conf.json'));
        $this->API->setRootDir(realpath('tests-data')); // unit test only
        $this->setAdmin();
    }


    public function testEmptyConf()
    {
        $this->expectException(\Exception::class);
        $this->API->loadConf('tests/empty.json');
        $this->path = '/cmsapi/v1/content';
        $this->SERVER = ['REQUEST_URI' => $this->path,    'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);

        $response = $this->API->processAPI();
        $result = $response->getResult();

        $this->assertTrue($result != null);
        $this->assertJsonStringEqualsJsonString('[
          {"type":"calendar", "labels": [ {"i18n":"en", "label":"Calendar"}, {"i18n":"fr", "label":"Calendrier"}]},
          {"type":"news", "labels": [ {"i18n":"en", "label":"News"}, {"i18n":"fr", "label":"Actualités"}]}
        ]', $result);
        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
    }
    public function testTypes()
    {
        $this->path = '/cmsapi/v1/content';
        $this->SERVER = ['REQUEST_URI' => $this->path,    'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);

        $response = $this->API->processAPI();
        $result = $response->getResult();

        $this->assertTrue($result != null);
        $this->assertJsonStringEqualsJsonString('[
          {"type":"calendar", "labels": [ {"i18n":"en", "label":"Calendar"}, {"i18n":"fr", "label":"Calendrier"}]},
          {"type":"news", "labels": [ {"i18n":"en", "label":"News"}, {"i18n":"fr", "label":"Actualités"}]}
        ]', $result);
        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
    }

    public function testPostSuccess()
    {

        // echo 'testPostSuccess: ' . $this->memory();
        $this->path = '/cmsapi/v1/content/calendar';
        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];

        $recordStr = file_get_contents($this->API->getPublicDirPath() . '/big.json');
        $this->POST = ['requestbody' => $recordStr];
        unset($recordStr);

        // echo 'new CmsApi: ' . $this->memory();

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);
        // echo 'setRequest: ' . $this->memory();

        // echo 'authorize: ' . $this->memory();
        $response = $this->API->processAPI();
        $result = $response->getResult();
        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
        // echo 'processAPI: ' . $this->memory();
        $this->assertTrue($result != null && $result != '');
        $jsonResult = json_decode($result);
        $this->assertTrue($jsonResult->{'timestamp'} != '');
    }


    public function testGetCalendarList()
    {
        $this->path = '/cmsapi/v1/content/calendar';


        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];
        $this->GET = ['requestbody' => '{}'];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);

        $response = $this->API->processAPI();
        $result = $response->getResult();
        $this->printError($response);
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($result != null && $result != '');

        $this->assertTrue(strpos($result, '{"filename":"1.json","id":"1"}') !== false);
    }

    public function testGetByGuest()
    {
        $this->setGuest();
        $this->path = '/cmsapi/v1/content/calendar/1';

        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);

        $response = $this->API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(403, $response->getCode());
        $this->assertTrue($result != null && $result != '');

        $this->assertJsonStringEqualsJsonString('{"error":"wrong role"}', $result);
    }

    public function testGetCalendarRecord()
    {
        $this->path = '/cmsapi/v1/content/calendar/1';


        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);

        $response = $this->API->processAPI();
        $result = $response->getResult();
        $this->printError($response);
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($result != null && $result != '');
        $this->assertTrue(strpos($result, '"id"') !== false);
        $this->assertTrue(strpos($result, '"type"') !== false);
        $this->assertTrue(strpos($result, '"date"') !== false);
        $this->assertTrue(strpos($result, '"title"') !== false);
    }

    public function testGetCalendarError()
    {
        $this->path = '/cmsapi/v1/content/calendar/999999999';

        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);

        $response = $this->API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(404, $response->getCode());
    }

    public function testGetFile()
    {
        $this->path = '/cmsapi/v1/file';

        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];
        $this->GET = ['file' => 'calendar/index/metadata.json'];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);

        $response = $this->API->processAPI();
        $result = $response->getResult();
        $this->printError($response);
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($result != null && $result != '');

        $this->assertTrue(strpos($result, '"id"') !== false);
        $this->assertTrue(strpos($result, '"organization"') !== false);
        $this->assertTrue(strpos($result, '"date"') !== false);
        $this->assertTrue(strpos($result, '"title"') !== false);
    }

    public function testDelete()
    {
        $id = 'exampleid';


        //clone backup to directory
        $recordfile = $this->API->getPublicDirPath() . '/calendar/' . $id . '.json';
        copy($this->API->getPublicDirPath() . '/calendar/backup/' . $id . '.json', $recordfile);

        $this->path = '/cmsapi/v1/content/calendar/' . $id;



        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'DELETE', 'HTTP_ORIGIN' => 'foobar'];


        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);

        $response = $this->API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($result != null && $result != '');

        $this->assertTrue(!file_exists($recordfile));

        $index_data = file_get_contents($this->API->getPublicDirPath() . '/calendar/index/index.json');

        $this->assertTrue(!strpos($index_data, $id));
    }


    public function testGetIndex()
    {
        $this->path = '/cmsapi/v1/index/calendar';


        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);

        $response = $this->API->processAPI();
        $result = $response->getResult();
        $this->printError($response);
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($result != null && $result != '');
        $index_data = file_get_contents($this->API->getPublicDirPath() . '/calendar/index/index.json');

        $this->assertJsonStringEqualsJsonString($index_data, $result);
    }
}
