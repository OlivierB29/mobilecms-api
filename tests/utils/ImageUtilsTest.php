<?php

declare(strict_types=1);
namespace mobilecms\utils;

use PHPUnit\Framework\TestCase;

final class ImageUtilsTest extends TestCase
{
    public function testResizeJpeg()
    {
        $src = 'tests-data/imagesutils/tennis-178696_640.jpg';
        $dest = 'tests-data/imagesutils/tennis-178696_320.jpg';
        $u = new ImageUtils();
        $result = $u->resize($src, $dest, 320);
        $this->assertTrue(!empty($result));
        $this->assertTrue(\file_exists($dest));
    }

    public function testResizeJpegTooBig()
    {
        $src = 'tests-data/imagesutils/tennis-178696_640.jpg';
        $dest = 'tests-data/imagesutils/tennis-178696_1000.jpg';
        $u = new ImageUtils();
        $result = $u->resize($src, $dest, 1000);
        $this->assertTrue(empty($result));
        $this->assertFalse(\file_exists($dest));
    }

    public function testResizePng()
    {
        $src = 'tests-data/imagesutils/tennis-178696_640.png';
        $dest = 'tests-data/imagesutils/tennis-178696_320.png';
        $u = new ImageUtils();
        $result = $u->resize($src, $dest, 320);
        $this->assertTrue(!empty($result));
        $this->assertTrue(\file_exists($dest));
    }

    public function testResizePngTooBig()
    {
        $src = 'tests-data/imagesutils/tennis-178696_640.png';
        $dest = 'tests-data/imagesutils/tennis-178696_1000.png';
        $u = new ImageUtils();
        $result = $u->resize($src, $dest, 1000);
        $this->assertTrue(empty($result));
        $this->assertFalse(\file_exists($dest));
    }

    public function testCreateThumbnails()
    {
        $sizes = [ 100, 200, 300, 400, 500 ];
        $src = 'tests-data/imagesutils/tennis-178696_640.jpg';
        $dir = 'tests-data/imagesutils/thumbnails';
        $u = new ImageUtils();
        $result = $u->multipleResize($src, $dir, $sizes);

        $this->assertTrue(count($result) === count($sizes));
        ;
    }


    public function testCreateThumbnailsTooBig()
    {
        $sizes = [ 100, 200, 300, 400, 500, 1000 ];
        $src = 'tests-data/imagesutils/tennis-178696_640.jpg';
        $dir = 'tests-data/imagesutils/thumbnails';
        $u = new ImageUtils();
        $result = $u->multipleResize($src, $dir, $sizes);

        $this->assertTrue(count($result) === 5);
        ;
    }
}
