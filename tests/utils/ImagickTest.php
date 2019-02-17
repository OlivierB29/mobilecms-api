<?php

declare(strict_types=1);
namespace mobilecms\utils;

use PHPUnit\Framework\TestCase;

final class ImagickTest extends TestCase
{
    public function testResizeJpeg()
    {
        $src = 'tests-data/imagesutils/baseball-field-1149153.jpg';
        $dest = 'tests-data/imagick/baseball-field-1149153_1024.jpg';
        $u = new ImageUtils();
        $u->setQuality(75);
        $u->setImagick(true);
        $result = $u->resize($src, $dest, 1024);
        $this->assertTrue(!empty($result));
        $this->assertTrue(\file_exists($dest));
    }

    public function testResizeJpegTooBig()
    {
        $src = 'tests-data/imagesutils/baseball-field-1149153.jpg';
        $dest = 'tests-data/imagick/baseball-field-1149153_8000.jpg';
        $u = new ImageUtils();
        $u->setQuality(75);
        $u->setImagick(true);
        $result = $u->resize($src, $dest, 8000);
        $this->assertTrue(empty($result));
        $this->assertFalse(\file_exists($dest));
    }

    public function testResizePng()
    {
        $src = 'tests-data/imagesutils/baseball-field-1149153_2048.png';
        $dest = 'tests-data/imagick/baseball-field-1149153_1024.png';
        $u = new ImageUtils();
        $u->setQuality(75);
        $u->setImagick(true);
        $result = $u->resize($src, $dest, 1024);
        $this->assertTrue(!empty($result));
        $this->assertTrue(\file_exists($dest));
    }

    public function testResizePngTooBig()
    {
        $src = 'tests-data/imagesutils/baseball-field-1149153_2048.png';
        $dest = 'tests-data/imagick/baseball-field-1149153_8000.png';
        $u = new ImageUtils();
        $u->setQuality(75);
        $u->setImagick(true);
        $result = $u->resize($src, $dest, 8000);
        $this->assertTrue(empty($result));
        $this->assertFalse(\file_exists($dest));
    }

    public function testCreateThumbnails()
    {
        $sizes = [ 150, 300, 672, 768, 1024 ];
        $src = 'tests-data/imagesutils/baseball-field-1149153.jpg';
        $dir = 'tests-data/imagick/thumbnails';
        $u = new ImageUtils();
        $u->setQuality(75);
        $u->setImagick(true);
        $result = $u->multipleResize($src, $dir, $sizes);

        $this->assertTrue(count($result) === count($sizes));
    }


    public function testCreateThumbnailsTooBig()
    {
        $sizes = [ 150, 300, 672, 768, 1024, 8000 ];
        $src = 'tests-data/imagesutils/baseball-field-1149153.jpg';
        $dir = 'tests-data/imagick/thumbnails';
        $u = new ImageUtils();
        $u->setQuality(75);
        $u->setImagick(true);
        $result = $u->multipleResize($src, $dir, $sizes);

        $this->assertTrue(count($result) === 5);
    }
}
