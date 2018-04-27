<?php

declare(strict_types=1);
namespace mobilecms\utils;

use PHPUnit\Framework\TestCase;

final class PdfUtilsTest extends TestCase
{
    public function testResizePdf()
    {
        $src = 'tests-data/pdfutils/test.pdf';
        $dest = 'tests-data/pdfutils/test.jpg';
        $u = new PdfUtils();
        $result = $u->resize($src, $dest, 210);
        $this->assertTrue(!empty($result));
        $this->assertJsonStringEqualsJsonString('{"width":"210","height":"297","url":"test.jpg"}', \json_encode($result));
        $this->assertTrue(\file_exists($dest));
      //  \unlink($dest);
    }


    public function testCreatePdfThumbnails()
    {
        $sizes = [ 100, 200, 300, 400, 500 ];
        $src = 'tests-data/pdfutils/test.pdf';
        $dir = 'tests-data/pdfutils/thumbnails';
        $u = new PdfUtils();
        $result = $u->multipleResize($src, $dir, $sizes);
        $this->assertJsonStringEqualsJsonString('[{"width":"100","height":"142","url":"test-100.jpg"},{"width":"200","height":"283","url":"test-200.jpg"},{"width":"300","height":"425","url":"test-300.jpg"},{"width":"400","height":"566","url":"test-400.jpg"},{"width":"500","height":"708","url":"test-500.jpg"}]', \json_encode($result));
        $this->assertTrue(count($result) === count($sizes));

        // \unlink($dir);
    }
}
