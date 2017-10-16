<?php

declare(strict_types=1);
namespace mobilecms\api;

use PHPUnit\Framework\TestCase;

final class CmsApiTest extends TestCase
{
    private $user;
    private $token;
    private $conf;

    private $guest;
    private $guesttoken;

    private $memory1 = 0;
    private $memory2 = 0;

    protected function setUp()
    {
        $this->memory1 = 0;
        $this->memory2 = 0;

        $this->conf = json_decode(file_get_contents('tests/conf.json'));

        $service = new \mobilecms\utils\UserService(realpath('tests-data') . $this->conf->{'privatedir'} . '/users');

        $response = $service->getToken('editor@example.com', 'Sample#123456');
        $this->user = json_decode($response->getResult());
        $this->token = 'Bearer ' . $this->user->{'token'};

        $response = $service->getToken('guest@example.com', 'Sample#123456');
        $this->guest = json_decode($response->getResult());
        $this->guesttoken = 'Bearer ' . $this->guest->{'token'};

        $this->memory();
    }

    private function memory()
    {
        $this->memory1 = $this->memory2;

        $this->memory2 = memory_get_usage();

        return $this->memory2 - $this->memory1;
    }

    public function testTypes()
    {
        $path = '/restapi/v1/content';

        $headers = ['Authorization' => $this->token,    'apiKey' => '123'];
        $REQUEST = []; 
        $SERVER = ['REQUEST_URI' => $path,    'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];
        $GET = [];
        $POST = null;

        $API = new CmsApi($this->conf);
        $API->setRootDir(realpath('tests-data')); // unit test only
        $API->setRequest($REQUEST, $SERVER, $GET, $POST, $headers);

        $response = $API->processAPI();
        $result = $response->getResult();

        $this->assertTrue($result != null);
        $this->assertJsonStringEqualsJsonString('[
          {"type":"calendar", "labels": [ {"i18n":"en", "label":"Calendar"}, {"i18n":"fr", "label":"Calendrier"}]},
          {"type":"news", "labels": [ {"i18n":"en", "label":"News"}, {"i18n":"fr", "label":"Actualités"}]}
        ]', $result);
        $this->assertEquals(200, $response->getCode());
    }

    public function testPostSuccess()
    {
        // echo 'testPostSuccess: ' . $this->memory();
        $path = '/restapi/v1/content/calendar';

        $REQUEST = []; 
        $headers = ['Authorization' => $this->token];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];
        $GET = null;

        $API = new CmsApi($this->conf);
        $API->setRootDir(realpath('tests-data')); // unit test only
        $recordStr = file_get_contents($API->getPublicDirPath() . '/big.json');
        //$recordStr = '{"id":"10","type" : "calendar","date":"20150901","activity":"activitya","title":"some seminar of activity A","organization":"Some org","description":"some infos","url":"","location":"","startdate":"","enddate":"","updated":"","updatedby":""}';
        // echo 'recordStr: ' . $this->memory();
        $POST = ['requestbody' => $recordStr];
        unset($recordStr);

        // echo 'new CmsApi: ' . $this->memory();

        $API->setRequest($REQUEST, $SERVER, $GET, $POST, $headers);
        // echo 'setRequest: ' . $this->memory();

        // echo 'authorize: ' . $this->memory();
        $response = $API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(200, $response->getCode());
        // echo 'processAPI: ' . $this->memory();
        $this->assertTrue($result != null && $result != '');
        $jsonResult = json_decode($result);
        $this->assertTrue($jsonResult->{'timestamp'} != '');
    }

    /*
        public function testPut1()
        {
            $path = '/restapi/v1/content/calendar';
            $id = 'test_'.rand(0, 999999);
            $recordStr = '{"id":"'.$id.'","type" : "calendar","date":"20150901","activity":"activitya","title":"some seminar of activity A","organization":"Some org","description":"some infos","url":"","location":"","startdate":"","enddate":"","updated":"","updatedby":""}';

            $file = $this->conf->{'publicdir'} . $path . '/' . $id . '.json';

            if (file_exists($file)) {
                unlink($file);
            }
            $REQUEST = []; 
            $headers = ['Authorization' => $this->token];
            $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'PUT', 'HTTP_ORIGIN' => 'foobar'];
            $GET = null;
            $POST = ['requestbody' => $recordStr];

            $API = new CmsApi($this->conf);

            $API->setRequest($REQUEST, $SERVER, $GET, $POST, $headers);

            $response = $API->processAPI(); $result = $response->getResult();
            echo '!!!!!!!!!!!!!!!' . $result;


            $this->assertEquals(200, $response->getCode());
            $this->assertTrue($result != null && $result != '');


        }
    */
    public function testGetCalendarList()
    {
        $path = '/restapi/v1/content/calendar';
        $headers = ['Authorization' => $this->token];
        $REQUEST = []; 
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];
        $GET = ['requestbody' => '{}'];
        $POST = null;

        $API = new CmsApi($this->conf);
        $API->setRootDir(realpath('tests-data')); // unit test only
        $API->setRequest($REQUEST, $SERVER, $GET, $POST, $headers);

        $response = $API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($result != null && $result != '');
        $this->assertTrue(strpos($result, '{"filename":"1.json","id":"1"}') !== false);
    }

    public function testGetByGuest()
    {
        $path = '/restapi/v1/content/calendar/1';
        $headers = ['Authorization' => $this->guesttoken];
        $REQUEST = [];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];
        $GET = [];
        $POST = null;

        $API = new CmsApi($this->conf);
        $API->setRootDir(realpath('tests-data')); // unit test only
        $API->setRequest($REQUEST, $SERVER, $GET, $POST, $headers);

        $response = $API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(403, $response->getCode());
        $this->assertTrue($result != null && $result != '');

        $this->assertJsonStringEqualsJsonString(
          '{"error":"wrong role"}',
           $result);
    }

    public function testGetCalendarRecord()
    {
        $path = '/restapi/v1/content/calendar/1';
        $headers = ['Authorization' => $this->token];
        $REQUEST = []; 
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];
        $GET = [];
        $POST = null;

        $API = new CmsApi($this->conf);
        $API->setRootDir(realpath('tests-data')); // unit test only
        $API->setRequest($REQUEST, $SERVER, $GET, $POST, $headers);

        $response = $API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($result != null && $result != '');
        $this->assertTrue(strpos($result, '"id"') !== false);
        $this->assertTrue(strpos($result, '"type"') !== false);
        $this->assertTrue(strpos($result, '"date"') !== false);
        $this->assertTrue(strpos($result, '"title"') !== false);
    }

    public function testGetCalendarError()
    {
        $path = '/restapi/v1/content/calendar/999999999';
        $headers = ['Authorization' => $this->token];
        $REQUEST = []; 
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];
        $GET = [];
        $POST = null;

        $API = new CmsApi($this->conf);
        $API->setRootDir(realpath('tests-data')); // unit test only
        $API->setRequest($REQUEST, $SERVER, $GET, $POST, $headers);

        $response = $API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(404, $response->getCode());
    }

    public function testGetFile()
    {
        $path = '/restapi/v1/file';
        $headers = ['Authorization' => $this->token];
        $REQUEST = []; 
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];
        $GET = ['file' => 'calendar/index/metadata.json'];
        $POST = null;

        $API = new CmsApi($this->conf);
        $API->setRootDir(realpath('tests-data')); // unit test only
        $API->setRequest($REQUEST, $SERVER, $GET, $POST, $headers);

        $response = $API->processAPI();
        $result = $response->getResult();
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
        $API = new CmsApi($this->conf);
        $API->setRootDir(realpath('tests-data')); // unit test only
        $dir = $API->getPublicDirPath();

        //clone backup to directory
        $recordfile = $dir . '/calendar/' . $id . '.json';
        copy($dir . '/calendar/backup/' . $id . '.json', $recordfile);

        $path = '/restapi/v1/content/calendar/' . $id;

        $recordStr = '';

        $REQUEST = []; 
        $headers = ['Authorization' => $this->token];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'DELETE', 'HTTP_ORIGIN' => 'foobar'];
        $GET = [];
        $POST = null;

        $API->setRequest($REQUEST, $SERVER, $GET, $POST, $headers);

        $response = $API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($result != null && $result != '');

        $this->assertTrue(!file_exists($recordfile));

        $index_data = file_get_contents($dir . '/calendar/index/index.json');

        $this->assertTrue(!strpos($index_data, $id));
    }
}
