<?php

declare(strict_types=1);
namespace mobilecms\api;

use PHPUnit\Framework\TestCase;

final class FileApiTest extends AuthApiTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->API=new FileApi();
        $this->API->loadConf(realpath('tests/conf.json'));
        $this->API->setRootDir(realpath('tests-data')); // unit test only
        $this->setAdmin();
    }

    public function testEmptyConf()
    {
        $this->expectException(\Exception::class);
        $this->API->loadConf('tests/empty.json');
        $this->path = '/mobilecmsapi/v1/fileapi/download/calendar/4';
        $this->SERVER = ['REQUEST_URI' => $this->path,    'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);

        $this->API->processAPI();
    }

    public function testDownload()
    {
        $this->path = '/mobilecmsapi/v1/fileapi/download/calendar/4';
        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];

        $recordStr = '[{ "url": "https://mit-license.org/index.html", "title":"MIT licence"}]';

        $this->POST = ['requestbody' => $recordStr];
        unset($recordStr);


        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);

        $this->API->authorize($this->headers, $this->SERVER);

        $response = $this->API->processAPI();

        $this->printError($response);
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($response != null);

        // test JSON response
        $this->assertTrue(count($response->getResult()) === 1);
        $imageData = $response->getResult()[0];
        $this->assertTrue($imageData->{'url'} === 'index.html');
        $this->assertTrue($imageData->{'title'} === 'MIT licence');


        // test download
        $download = file_get_contents($this->API->getMediaDirPath() . '/calendar/4/index.html');
        $this->assertTrue(strpos($download, 'MIT License') !== false);
        $fileutil = new \mobilecms\utils\FileUtils();
        $fileutil->deleteDir($this->API->getMediaDirPath() . '/calendar/4');
    }

    public function testDownloadImage()
    {
        $this->path = '/mobilecmsapi/v1/fileapi/download/calendar/5';
        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];

        $recordStr = '[{ "url": "https://php.net/images/logos/new-php-logo.png", "title":"php logo"}]';

        $this->POST = ['requestbody' => $recordStr];
        unset($recordStr);


        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);

        $this->API->authorize($this->headers, $this->SERVER);

        $response = $this->API->processAPI();

        $this->printError($response);
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($response != null);

        // test JSON response
        $this->assertTrue(count($response->getResult()) === 1);
        $imageData = $response->getResult()[0];
        $this->assertTrue($imageData->{'url'} === 'new-php-logo.png');
        $this->assertTrue($imageData->{'title'} === 'php logo');

        // test download
        $this->assertTrue(\file_exists($this->API->getMediaDirPath() . '/calendar/5/new-php-logo.png'));
        $fileutil = new \mobilecms\utils\FileUtils();
        $fileutil->deleteDir($this->API->getMediaDirPath() . '/calendar/5');
    }


    public function testDownloadNoFiles()
    {
        $this->path = '/mobilecmsapi/v1/fileapi/download/calendar/4';
        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];
        $recordStr = '[{ "url": "/foobar/foo.html", "title":"foobar"}]';
        $this->POST = ['requestbody' => $recordStr];
        unset($recordStr);
        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);
        $this->API->authorize($this->headers, $this->SERVER);
        $response = $this->API->processAPI();
        $this->assertEquals(500, $response->getCode());
    }

    public function testDelete()
    {
        $filename = 'testdelete.pdf';
        $record = '/calendar/2';
        // tests-data/fileapi/save -> tests-data/fileapi/media/calendar/2/



        $destfile = $this->API->getMediaDirPath() . $record . '/' . $filename;

        copy('tests-data/fileapi/save/' . $filename, $destfile);

        // assert file exists before API call
        $this->assertTrue(file_exists($destfile));
        $this->path = '/mobilecmsapi/v1/fileapi/delete/calendar/2';

        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];

        $recordStr = '[{ "url": "testdelete.pdf", "title":"test"}]';

        $this->POST = ['requestbody' => $recordStr];
        unset($recordStr);

        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);

        $this->API->authorize($this->headers, $this->SERVER);

        $response = $this->API->processAPI();

        $this->printError($response);
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($response != null);

        // test deleted file
        $this->assertTrue(!file_exists($destfile));
    }

    public function testGet()
    {

          // echo 'testPostSuccess: ' . $this->memory();
        $this->path = '/mobilecmsapi/v1/fileapi/basicupload/calendar/1';
        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'GET', 'HTTP_ORIGIN' => 'foobar'];
        $this->GET = ['requestbody' => '{}'];
        $recordStr = '[{ "url": "https://mit-license.org/index.html", "title":"MIT licence"}]';


        unset($recordStr);





        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);

        $this->API->authorize($this->headers, $this->SERVER);

        $response = $this->API->processAPI();

        $this->printError($response);
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($response != null);
        $expected = '[{"title":"index.html","url":"index.html","size":2834,"mimetype":"text\/html"},{"title":"lorem ipsum.pdf","url":"lorem ipsum.pdf","size":24612,"mimetype":"application\/pdf"}]';

        $this->assertJsonStringEqualsJsonString($expected, $response->getEncodedResult());
    }

    public function testUploadFile()
    {
        // API request
        $record = '/calendar/3';
        $this->path = '/mobilecmsapi/v1/fileapi/basicupload' . $record;
        $filename = 'testupload.pdf';
        // mock file
        $mockUploadedFile = realpath('tests-data/fileapi/save/') . '123456789.pdf';
        copy('tests-data/fileapi/save/' . $filename, $mockUploadedFile);
        $files = [
        ['name'=>$filename,'type'=>'application/pdf','tmp_name'=> $mockUploadedFile,'error'=>0,'size'=>24612]
        ];

        $this->API->setDebug(true);
        $response = $this->filerequest('POST', $this->path, $files);

        $this->printError($response);
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($response != null);
        $expected = '[{"title":"testupload.pdf","url":"testupload.pdf","size":24612,"mimetype":"application\/pdf"}]';

        $this->assertJsonStringEqualsJsonString($expected, $response->getEncodedResult());

        $mediaFile = $this->API->getMediaDirPath() . $record . '/' . $filename;
        $this->assertTrue(file_exists($mediaFile));
        unlink($mediaFile);
    }

    public function testUploadFileDoesNotExist()
    {
        // API request
        $record = '/calendar/3';
        $this->path = '/mobilecmsapi/v1/fileapi/basicupload' . $record;
        $filename = 'wrongfile.pdf';
        // mock file
        $mockUploadedFile = realpath('tests-data/fileapi/save') . $filename;
        $files = [
        ['name'=>$filename,'type'=>'application/pdf','tmp_name'=> $mockUploadedFile,'error'=>0,'size'=>24612]
        ];

        // mock HTTP parameters

        $this->API->setDebug(true);


        $response = $this->filerequest('POST', $this->path, $files);

        $this->assertEquals(500, $response->getCode());

        $this->assertTrue($response != null);
        $expected = '{"error":"Uploaded file not found ' . $mockUploadedFile . '"}';

        $this->assertJsonStringEqualsJsonString($expected, $response->getEncodedResult());
    }


    public function testUploadFileForbiddenExtension()
    {
        // API request
        $record = '/calendar/3';
        $this->path = '/mobilecmsapi/v1/fileapi/basicupload' . $record;
        $filename = 'testupload.bmp';
        // mock file
        $mockUploadedFile = realpath('tests-data/fileapi/save') . $filename;
        $files = [
            ['name'=>$filename,'type'=>'image/bmp','tmp_name'=> $mockUploadedFile,'error'=>0,'size'=>24612]
            ];

        $this->API->setDebug(true);

        $response = $this->filerequest('POST', $this->path, $files);
        $this->assertEquals(500, $response->getCode());

        $this->assertTrue($response != null);
        $expected = '{"error":"forbidden file type"}';

        $this->assertJsonStringEqualsJsonString($expected, $response->getEncodedResult());
    }

    public function testThumbnails()
    {
        $record = '/calendar/2';
        $this->path = '/mobilecmsapi/v1/fileapi/thumbnails/calendar/2';

        $recordStr = '[{ "url": "tennis.jpg", "sizes": ["100", "200", "300"]}]';

        $this->POST = ['requestbody' => $recordStr];
        unset($recordStr);


        $response = $this->authrequest('POST', $this->path);


        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($response != null);

        $expected = '[{"width":"640","height":"476","url":"tennis.jpg","mimetype":"image\/jpeg","thumbnails":[{"width":"100","height":"74","url":"tennis-100.jpg"},{"width":"200","height":"149","url":"tennis-200.jpg"},{"width":"300","height":"223","url":"tennis-300.jpg"}]}]';

        $this->assertJsonStringEqualsJsonString($expected, $response->getEncodedResult());

        $this->assertTrue(file_exists($this->API->getMediaDirPath() . $record . '/thumbnails/tennis-100.jpg'));
        unlink($this->API->getMediaDirPath() . $record . '/thumbnails/tennis-100.jpg');
        $this->assertTrue(file_exists($this->API->getMediaDirPath() . $record . '/thumbnails/tennis-200.jpg'));
        unlink($this->API->getMediaDirPath() . $record . '/thumbnails/tennis-200.jpg');
        $this->assertTrue(file_exists($this->API->getMediaDirPath() . $record . '/thumbnails/tennis-300.jpg'));
        unlink($this->API->getMediaDirPath() . $record . '/thumbnails/tennis-300.jpg');
    }

    public function testThumbnailsDefaultSizes()
    {
        $record = '/calendar/2';
        $this->path = '/mobilecmsapi/v1/fileapi/thumbnails/calendar/2';

        $recordStr = '[{ "url": "tennis.jpg"}]';

        $this->POST = ['requestbody' => $recordStr];
        unset($recordStr);

        $response = $this->authrequest('POST', $this->path);

        $this->printError($response);
        $this->assertEquals(200, $response->getCode());

        $this->assertTrue($response != null);

        $expected = '[{"width":"640","height":"476","url":"tennis.jpg","mimetype":"image\/jpeg","thumbnails":[{"width":"150","height":"112","url":"tennis-150.jpg"},{"width":"300","height":"223","url":"tennis-300.jpg"}]}]';

        $this->assertJsonStringEqualsJsonString($expected, $response->getEncodedResult());

        $this->assertTrue(file_exists($this->API->getMediaDirPath() . $record . '/thumbnails/tennis-150.jpg'));
        unlink($this->API->getMediaDirPath() . $record . '/thumbnails/tennis-150.jpg');
        $this->assertTrue(file_exists($this->API->getMediaDirPath() . $record . '/thumbnails/tennis-300.jpg'));
        unlink($this->API->getMediaDirPath() . $record . '/thumbnails/tennis-300.jpg');
    }

    protected function filerequest($verb, $path, $files): \mobilecms\rest\Response
    {
        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => $verb, 'HTTP_ORIGIN' => 'foobar'];
        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers, $files);
        $this->API->authorize($this->headers, $this->SERVER);
        return $this->API->processAPI();
    }
}
