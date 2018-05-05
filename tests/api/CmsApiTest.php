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

        $response = $this->request('GET', $this->path);


        $this->assertTrue($response != null);
        $this->assertJsonStringEqualsJsonString('[
          {"type":"calendar", "labels": [ {"i18n":"en", "label":"Calendar"}, {"i18n":"fr", "label":"Calendrier"}]},
          {"type":"news", "labels": [ {"i18n":"en", "label":"News"}, {"i18n":"fr", "label":"Actualités"}]}
        ]', $response->getEncodedResult());
        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
    }
    public function testTypes()
    {
        $this->path = '/cmsapi/v1/content';
        $this->SERVER = ['REQUEST_URI' => $this->path,    'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];
        $response = $this->request('GET', $this->path);

        $this->assertTrue($response != null);
        $this->assertJsonStringEqualsJsonString('[
          {"type":"calendar", "labels": [ {"i18n":"en", "label":"Calendar"}, {"i18n":"fr", "label":"Calendrier"}]},
          {"type":"news", "labels": [ {"i18n":"en", "label":"News"}, {"i18n":"fr", "label":"Actualités"}]}
        ]', $response->getEncodedResult());
        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
    }

    public function testPostSuccess()
    {

        // echo 'testPostSuccess: ' . $this->memory();
        $this->path = '/cmsapi/v1/content/calendar';


        $recordStr = file_get_contents($this->API->getPublicDirPath() . '/big.json');
        $this->POST = ['requestbody' => $recordStr];
        unset($recordStr);
        $response = $this->request('POST', $this->path);


        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
        // echo 'processAPI: ' . $this->memory();
        $this->assertTrue($response->getResult() != null && $response->getResult() != '');
    }

    public function testEmptyToken()
    {
        $this->path = '/cmsapi/v1/content/calendar';
        $this->headers=['Authorization' => ''];



        $this->GET = ['requestbody' => '{}'];
        $response = $this->request('GET', $this->path);

        $this->assertEquals(401, $response->getCode());
    }

    public function testGetCalendarList()
    {
        $this->path = '/cmsapi/v1/content/calendar';


        $this->GET = ['requestbody' => '{}'];
        $response = $this->request('GET', $this->path);


        $this->printError($response);
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($response != null);

        $this->assertTrue(strpos($response->getEncodedResult(), '{"filename":"1.json","id":"1"}') !== false);
    }

    public function testGetByGuest()
    {
        $this->setGuest();
        $this->path = '/cmsapi/v1/content/calendar/1';

        $response = $this->request('GET', $this->path);


        $this->assertEquals(403, $response->getCode());
        $this->assertTrue($response != null);

        $this->assertJsonStringEqualsJsonString('{"error":"wrong role"}', $response->getEncodedResult());
    }

    public function testGetCalendarRecord()
    {
        $this->path = '/cmsapi/v1/content/calendar/1';


        $response = $this->request('GET', $this->path);


        $this->printError($response);
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($response != null);

        $this->assertTrue($response->getResult()->{'id'} === '1');
        $this->assertTrue($response->getResult()->{'type'} === 'calendar');

        $this->assertFalse(empty($response->getResult()->{'date'}));
        $this->assertFalse(empty($response->getResult()->{'title'}));
    }

    public function testGetCalendarError()
    {
        $this->path = '/cmsapi/v1/content/calendar/999999999';

        $response = $this->request('GET', $this->path);


        $this->assertEquals(404, $response->getCode());
    }

    public function testDelete()
    {
        $id = 'exampleid';


        //clone backup to directory
        $recordfile = $this->API->getPublicDirPath() . '/calendar/' . $id . '.json';
        copy($this->API->getPublicDirPath() . '/calendar/backup/' . $id . '.json', $recordfile);

        $fileutils = new \mobilecms\utils\FileUtils();
        $fileutils->copydir($this->API->getMediaDirPath() . '/calendar/backup/' . $id, $this->API->getMediaDirPath() . '/calendar/' . $id);

        $this->path = '/cmsapi/v1/content/calendar/' . $id;



        $response = $this->request('DELETE', $this->path);


        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($response != null);

        $this->assertTrue(!file_exists($recordfile));

        $this->assertJsonStringEqualsJsonString('{}', $response->getEncodedResult());
    }


    public function testGetIndex()
    {
        $this->path = '/cmsapi/v1/index/calendar';


        $response = $this->request('GET', $this->path);


        $this->printError($response);
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($response != null);
        $index_data = file_get_contents($this->API->getPublicDirPath() . '/calendar/index/index.json');

        $this->assertJsonStringEqualsJsonString($index_data, $response->getEncodedResult());
    }

    public function testGetMetadata()
    {
        $this->path = '/cmsapi/v1/metadata/calendar';


        $response = $this->request('GET', $this->path);


        $this->printError($response);
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($response != null);
        $index_data = file_get_contents($this->API->getPublicDirPath() . '/calendar/index/metadata.json');

        $this->assertJsonStringEqualsJsonString($index_data, $response->getEncodedResult());
    }

    public function testTemplate()
    {
        $this->path = '/cmsapi/v1/template/calendar';


        $response = $this->request('GET', $this->path);


        $this->printError($response);
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($response != null);
        $index_data = file_get_contents($this->API->getPublicDirPath() . '/calendar/index/new.json');

        $this->assertJsonStringEqualsJsonString($index_data, $response->getEncodedResult());
    }
}
