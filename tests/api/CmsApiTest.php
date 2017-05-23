<?php

declare(strict_types=1);
require_once 'conf.php';

use PHPUnit\Framework\TestCase;

final class CmsApiTest extends TestCase
{
    private $user;
    private $token;
    private $conf;

    protected function setUp()
    {
        $this->conf = json_decode('{}');
        $this->conf->{'enableheaders'} = 'false';
        $this->conf->{'enableapikey'} = 'false';
        $this->conf->{'publicdir'} = HOME.'/tests-data/public';
        $this->conf->{'privatedir'} = HOME.'/tests-data/private';
        $this->conf->{'apikeyfile'} = HOME.'/tests-data/private/apikeys/key1.json';

        $service = new UserService('tests-data/userservice');
        $response = $service->getToken('test@example.com', 'Sample#123456');

        $this->user = json_decode($response->getResult());

        $this->token = 'Bearer '.$this->user->{'token'};
    }

    public function testTypes()
    {
        $path = '/api/v1/content';

        $headers = ['Authorization' => $this->token,    'apiKey' => '123'];
        $REQUEST = ['path' => $path];
        $SERVER = ['REQUEST_URI' => $path,    'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];
        $GET = [];
        $POST = null;

        $API = new CmsApi($this->conf);
        $API->setRequest($REQUEST, $SERVER, $GET, $POST);
        $API->authorize($headers, $SERVER);
        $result = $API->processAPI();
        $this->assertTrue($result != null);
        $this->assertJsonStringEqualsJsonString('[{"type":"calendar"},{"type":"news"}]', $result);
    }

    public function testPost1()
    {
        $path = '/api/v1/content/calendar';

        $recordStr = '{"id":"10","type" : "calendar","date":"201509","activity":"activitya","title":"some seminar of activity A","organization":"Some org","description":"some infos","url":"","location":"","startdate":"","enddate":"","updated":"","updatedby":""}';

        $REQUEST = ['path' => $path];
        $headers = ['Authorization' => $this->token, 'apiKey' => '123'];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];
        $GET = null;
        $POST = ['requestbody' => $recordStr];

        $API = new CmsApi($this->conf);

        $API->setRequest($REQUEST, $SERVER, $GET, $POST);
        $API->authorize($headers, $SERVER);
        $result = $API->processAPI();
        $this->assertTrue($result != null && $result != '');
    }

    public function testPut1()
    {
        $path = '/api/v1/content/calendar';
        $id = 'test_'.rand(0, 999999);
        $recordStr = '{"id":"'.$id.'","type" : "calendar","date":"201509","activity":"activitya","title":"some seminar of activity A","organization":"Some org","description":"some infos","url":"","location":"","startdate":"","enddate":"","updated":"","updatedby":""}';

        $REQUEST = ['path' => $path];
        $headers = ['Authorization' => $this->token, 'apiKey' => '123'];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'PUT', 'HTTP_ORIGIN' => 'foobar'];
        $GET = null;
        $POST = ['requestbody' => $recordStr];

        $API = new CmsApi($this->conf);

        $API->setRequest($REQUEST, $SERVER, $GET, $POST);
        $API->authorize($headers, $SERVER);
        $result = $API->processAPI();
        $this->assertTrue($result != null && $result != '');
    }

    public function testGetCalendarList()
    {
        $path = '/api/v1/content/calendar';
        $headers = ['Authorization' => $this->token, 'apiKey' => '123'];
        $REQUEST = ['path' => $path];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];
        $GET = ['requestbody' => '{}'];
        $POST = null;

        $API = new CmsApi($this->conf);
        $API->setRequest($REQUEST, $SERVER, $GET, $POST);
        $API->authorize($headers, $SERVER);
        $result = $API->processAPI();

        $this->assertTrue($result != null && $result != '');
        $this->assertTrue(strpos($result, '{"filename":"1.json","id":"1"}') !== false);
    }

    public function testGetCalendarRecord()
    {
        $path = '/api/v1/content/calendar/1';
        $headers = ['Authorization' => $this->token, 'apiKey' => '123'];
        $REQUEST = ['path' => $path];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];
        $GET = [];
        $POST = null;

        $API = new CmsApi($this->conf);
        $API->setRequest($REQUEST, $SERVER, $GET, $POST);
        $API->authorize($headers, $SERVER);
        $result = $API->processAPI();

        $this->assertTrue($result != null && $result != '');
        $this->assertTrue(strpos($result, '"id"') !== false);
        $this->assertTrue(strpos($result, '"type"') !== false);
        $this->assertTrue(strpos($result, '"date"') !== false);
        $this->assertTrue(strpos($result, '"title"') !== false);
    }

    public function testGetCalendarError()
    {
        $path = '/api/v1/content/calendar/999999999';
        $headers = ['Authorization' => $this->token, 'apiKey' => '123'];
        $REQUEST = ['path' => $path];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];
        $GET = [];
        $POST = null;

        $API = new CmsApi($this->conf);
        $API->setRequest($REQUEST, $SERVER, $GET, $POST);
        $API->authorize($headers, $SERVER);
        $result = $API->processAPI();

        $this->assertTrue($result != null && $result === '{}');
    }

    public function testGetFile()
    {
        $path = '/api/v1/file';
        $headers = ['Authorization' => $this->token, 'apiKey' => '123'];
        $REQUEST = ['path' => $path];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];
        $GET = ['file' => 'calendar/index/metadata.json'];
        $POST = null;

        $API = new CmsApi($this->conf);
        $API->setRequest($REQUEST, $SERVER, $GET, $POST);
        $API->authorize($headers, $SERVER);
        $result = $API->processAPI();

        $this->assertTrue($result != null && $result != '');

        $this->assertTrue(strpos($result, '"id"') !== false);
        $this->assertTrue(strpos($result, '"organization"') !== false);
        $this->assertTrue(strpos($result, '"date"') !== false);
        $this->assertTrue(strpos($result, '"title"') !== false);
    }

    public function testDelete()
    {
        $dir = $this->conf->{'publicdir'};

        copy($dir.'/calendar/delete.backup.json', $dir.'/calendar/delete.json');

        $path = '/api/v1/content/calendar';

        $recordStr = '{"id":"delete","type" : "calendar","date":"201509","activity":"activitya","title":"some seminar of activity A","organization":"Some org","description":"some infos","url":"","location":"","startdate":"","enddate":"","updated":"","updatedby":""}';

        $REQUEST = ['path' => $path];
        $headers = ['Authorization' => $this->token, 'apiKey' => '123'];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'DELETE', 'HTTP_ORIGIN' => 'foobar'];
        $GET = null;
        $POST = ['requestbody' => $recordStr];

        $API = new CmsApi($this->conf);

        $API->setRequest($REQUEST, $SERVER, $GET, $POST);
        $API->authorize($headers, $SERVER);
        $result = $API->processAPI();
        $this->assertTrue($result != null && $result != '');

        $this->assertTrue(!file_exists($dir.'/calendar/delete.json'));
    }
}
