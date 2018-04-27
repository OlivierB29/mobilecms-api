<?php

declare(strict_types=1);
namespace mobilecms\utils;

use PHPUnit\Framework\TestCase;

final class PdfUtilsTest extends TestCase
{
    public function testResizePdf()
    {
        $src = 'tests-data/pdfutils/document.pdf';
        $dest = 'tests-data/pdfutils/document.jpg';
        $u = new PdfUtils();
        $result = $u->resize($src, $dest, 210);
        $this->assertTrue(!empty($result));
        $this->assertJsonStringEqualsJsonString('{"width":"210","height":"297","url":"document.jpg"}', \json_encode($result));
        $this->assertTrue(\file_exists($dest));
      //  \unlink($dest);
    }


    public function testCreatePdfThumbnails()
    {
        $sizes = [ 100, 200, 300, 400, 500 ];
        $src = 'tests-data/pdfutils/document.pdf';
        $dir = 'tests-data/pdfutils/thumbnails';
        $u = new PdfUtils();
        $result = $u->multipleResize($src, $dir, $sizes);
        $this->assertJsonStringEqualsJsonString('[{"width":"100","height":"142","url":"document-100.jpg"},{"width":"200","height":"283","url":"document-200.jpg"},{"width":"300","height":"425","url":"document-300.jpg"},{"width":"400","height":"566","url":"document-400.jpg"},{"width":"500","height":"708","url":"document-500.jpg"}]', \json_encode($result));
        $this->assertTrue(count($result) === count($sizes));

        // \unlink($dir);
    }
}
