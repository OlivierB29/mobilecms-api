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
        $this->assertTrue($result);
        $this->assertTrue(\file_exists($dest));
    }

    public function testResizeJpegTooBig()
    {
        $src = 'tests-data/imagesutils/tennis-178696_640.jpg';
        $dest = 'tests-data/imagesutils/tennis-178696_1000.jpg';
        $u = new ImageUtils();
        $result = $u->resize($src, $dest, 1000);
        $this->assertFalse($result);
        $this->assertFalse(\file_exists($dest));
    }

    public function testResizePng()
    {
        $src = 'tests-data/imagesutils/tennis-178696_640.png';
        $dest = 'tests-data/imagesutils/tennis-178696_320.png';
        $u = new ImageUtils();
        $result = $u->resize($src, $dest, 320);
        $this->assertTrue($result);
        $this->assertTrue(\file_exists($dest));
    }

    public function testResizePngTooBig()
    {
        $src = 'tests-data/imagesutils/tennis-178696_640.png';
        $dest = 'tests-data/imagesutils/tennis-178696_1000.png';
        $u = new ImageUtils();
        $result = $u->resize($src, $dest, 1000);
        $this->assertFalse($result);
        $this->assertFalse(\file_exists($dest));
    }
}
