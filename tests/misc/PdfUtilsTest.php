<?php

declare(strict_types=1);
namespace mobilecms\utils;

use PHPUnit\Framework\TestCase;

/**
* Imagick doesn't seem to work with travis ci
*/
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
        \unlink($dest);
    }


    public function testCreatePdfThumbnails()
    {
        $sizes = [ 100, 200, 300 ];
        $src = 'tests-data/pdfutils/document.pdf';
        $dir = 'tests-data/pdfutils/thumbnails';
        $u = new PdfUtils();
        $result = $u->multipleResize($src, $dir, $sizes);
        $this->assertJsonStringEqualsJsonString('[{"width":"100","height":"142","url":"document-100.jpg"},{"width":"200","height":"283","url":"document-200.jpg"},{"width":"300","height":"425","url":"document-300.jpg"}]', \json_encode($result));
        $this->assertTrue(count($result) === count($sizes));
        $this->deleteDir($dir);
    }

    private function deleteDir($dir)
    {
        $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}
