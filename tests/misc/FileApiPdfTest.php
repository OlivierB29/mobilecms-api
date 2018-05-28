<?php

declare(strict_types=1);
namespace mobilecms\api;

use PHPUnit\Framework\TestCase;

final class FileApiPdfTest extends AuthApiTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->API=new FileApi();
        $this->API->loadConf(realpath('tests/conf.json'));
        $this->API->setRootDir(realpath('tests-data')); // unit test only
        $this->setAdmin();
    }

    public function testUploadPdfFileAndThumbnails()
    {
        // API request
        $record = '/calendar/3';
        $this->path = '/mobilecmsapi/v1/fileapi/basicupload' . $record;
        $filename = 'testupload2.pdf';
        // mock file
        $mockUploadedFile = realpath('tests-data/fileapi/save/') . 'foobar123.pdf';
        copy('tests-data/fileapi/save/' . $filename, $mockUploadedFile);
        $files = [
        ['name'=>$filename,'type'=>'application/pdf','tmp_name'=> $mockUploadedFile,'error'=>0,'size'=>24612]
        ];

        //
        // UPLOAD
        //
        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];
        $this->API->setDebug(true);
        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers, $files);
        $this->API->authorize($this->headers, $this->SERVER);
        $response = $this->API->processAPI();

        $this->printError($response);
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($response != null);
        $expected = '[{"title":"' . $filename . '","url":"' . $filename . '","size":163452,"mimetype":"application\/pdf"}]';
        $this->assertJsonStringEqualsJsonString($expected, $response->getEncodedResult());


        //
        // THUMBNAILS
        //
        $this->path = '/mobilecmsapi/v1/fileapi/thumbnails/calendar/3';
        $this->SERVER = ['REQUEST_URI' => $this->path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];
        $recordStr = '[{ "url": "' . $filename . '", "sizes": ["100", "200"]}]';
        $this->POST = ['requestbody' => $recordStr];
        unset($recordStr);
        $this->API->setRequest($this->REQUEST, $this->SERVER, $this->GET, $this->POST, $this->headers);
        $this->API->authorize($this->headers, $this->SERVER);
        $response = $this->API->processAPI();

        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($response != null);
        $expected = '[{"mimetype":"application\/pdf","url":"testupload2.pdf","thumbnails":[{"width":"100","height":"142","url":"testupload2-100.jpg"},{"width":"200","height":"283","url":"testupload2-200.jpg"}]}]';
        $this->assertJsonStringEqualsJsonString($expected, $response->getEncodedResult());
        $mediaFile = $this->API->getMediaDirPath() . $record . '/' . $filename;
        $this->assertTrue(file_exists($mediaFile));
        //unlink($mediaFile);
    }
}
