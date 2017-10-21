<?php

declare(strict_types=1);
namespace mobilecms\utils;

use PHPUnit\Framework\TestCase;

final class UrlUtilsTest extends TestCase
{
    public function testPathParam()
    {
        $u = new UrlUtils();
        //
        // $test = preg_match('(\{[-a-zA-Z0-9_]*\})', '{paramvalue}', $matches, PREG_OFFSET_CAPTURE);
        // $this->assertEquals(1, $test);
        // $this->assertEquals('{paramvalue}', $matches[0][0]);



        $this->assertTrue($u->isPathParameter('{paramvalue}'));
        $this->assertFalse($u->isPathParameter('foo'));
    }

    public function testMatch()
    {
        $matches = [];
        $u = new UrlUtils();
        $test = $u->match('/foo/bar', '/aaaaaaaaa/bar');
        $this->assertFalse($test);

        $matches = [];
        $test = $u->match('/foo/bar', '/a/b/c/d');
        $this->assertFalse($test);

        $matches = [];
        $test = $u->match('/foo/bar', '/foo/bar');
        $this->assertTrue($test);

        $matches = [];
        $test = $u->match('/foo/{bar}', '/foo/123', $matches);
        $this->assertTrue($test);
        $this->assertEquals('123', $matches['bar']);

        $matches = [];
        $test = $u->match('/foo/{bar}/lorem/{ipsum}', '/foo/123/lorem/aaa', $matches);
        $this->assertTrue($test);
        $this->assertEquals('123', $matches['bar']);
        $this->assertEquals('aaa', $matches['ipsum']);
    }
}