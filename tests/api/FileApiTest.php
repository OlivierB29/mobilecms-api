<?php

declare(strict_types=1);
require_once 'conf.php';

use PHPUnit\Framework\TestCase;

final class FileApiTest extends TestCase
{
    private $conf;

    protected function setUp()
    {
        $this->conf = json_decode(file_get_contents('tests/conf.json'));

        $service = new UserService(realpath('tests-data').$this->conf->{'privatedir'}.'/users');
        $response = $service->getToken('editor@example.com', 'Sample#123456');
        $this->user = json_decode($response->getResult());
        $this->token = 'Bearer '.$this->user->{'token'};
    }

    public function testDownload()
    {
        /*
        *
         * Sample request body :
         * [{ "url": "http://wwww.example.com/foobar.pdf", "title":"Foobar.pdf"}].

        protected function download()
        */

        // echo 'testPostSuccess: ' . $this->memory();
        $path = '/fileapi/v1/download/calendar/1';

        $REQUEST = []; // $REQUEST = ['path' => $path];
        $headers = ['Authorization' => $this->token];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];
        $GET = null;
        $recordStr = '[{ "url": "https://mit-license.org/index.html", "title":"MIT licence"}]';

        $POST = ['requestbody' => $recordStr];
        unset($recordStr);

        $API = new FileApi($this->conf);
        $API->setRootDir(realpath('tests-data'));

        $API->setRequest($REQUEST, $SERVER, $GET, $POST, $headers);

        $API->authorize($headers, $SERVER);

        $response = $API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($result != null && $result != '');

        // test JSON response

        $this->assertTrue(strpos($result, 'title') !== false);
        $this->assertTrue(strpos($result, 'url') !== false);
        $this->assertTrue(strpos($result, '"url":"index.html"') !== false);

        // test download
        $download = file_get_contents($API->getMediaDirPath().'/calendar/1/index.html');
        $this->assertTrue(strpos($download, 'MIT License') !== false);
    }

    public function testDelete()
    {
        $filename = 'testdelete.pdf';
        $record = '/calendar/2';
        // tests-data/fileapi/save -> tests-data/fileapi/media/calendar/2/
        $API = new FileApi($this->conf);
        $API->setRootDir(realpath('tests-data'));
        $destfile = $API->getMediaDirPath().$record.'/'.$filename;

        copy('tests-data/fileapi/save/'.$filename, $destfile);

        // assert file exists before API call
        $this->assertTrue(file_exists($destfile));
        $path = '/fileapi/v1/delete/calendar/2';

        $REQUEST = [];
        $headers = ['Authorization' => $this->token];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];
        $GET = null;
        $recordStr = '[{ "url": "testdelete.pdf", "title":"test"}]';

        $POST = ['requestbody' => $recordStr];
        unset($recordStr);

        $API->setRequest($REQUEST, $SERVER, $GET, $POST, $headers);

        $API->authorize($headers, $SERVER);

        $response = $API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($result != null && $result != '');

        // test deleted file
        $this->assertTrue(!file_exists($destfile));
    }

    public function testGet()
    {

          // echo 'testPostSuccess: ' . $this->memory();
        $path = '/fileapi/v1/basicupload/calendar/1';

        $REQUEST = []; // $REQUEST = ['path' => $path];
        $headers = ['Authorization' => $this->token];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];
        $GET = ['requestbody' => '{}'];
        $recordStr = '[{ "url": "https://mit-license.org/index.html", "title":"MIT licence"}]';

        $POST = null;
        unset($recordStr);

        $API = new FileApi($this->conf);
        $API->setRootDir(realpath('tests-data'));

        $API->setRequest($REQUEST, $SERVER, $GET, $POST, $headers);

        $API->authorize($headers, $SERVER);

        $response = $API->processAPI();
        $result = $response->getResult();
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($result != null && $result != '');
        $expected = '[{"title":"index.html","url":"index.html","size":2834,"mimetype":"text\/html"},{"title":"lorem ipsum.pdf","url":"lorem ipsum.pdf","size":24612,"mimetype":"application\/pdf"}]';

        $this->assertJsonStringEqualsJsonString($expected, $result);
    }
}
