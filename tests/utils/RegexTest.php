<?php

declare(strict_types=1);
namespace mobilecms\utils;

use PHPUnit\Framework\TestCase;

final class RegexTest extends TestCase
{


    public function testPath()
    {
        $test = preg_match('/\/content\/([-a-zA-Z0-9_]*)\/([\p{L})-_\.]+)/ui', 'aaaaaaa/bbbbbbbbbbb/content/aaaa/bbb', $matches, PREG_OFFSET_CAPTURE);
        $this->assertEquals(1, $test);
        $this->assertEquals(3, count($matches));

        $test = preg_match('/\/content\/([-a-zA-Z0-9_]*)\/([\p{L})-_\.]+)/ui', '/zzzzzcontent/aaaa/bbb', $matches, PREG_OFFSET_CAPTURE);
        $this->assertEquals(0, $test);
        $this->assertEquals(0, count($matches));

        $test = preg_match('/\/content\/([-a-zA-Z0-9_]*)\/([\p{L})-_\.]+)/ui', '/content/aaa/bbb', $matches, PREG_OFFSET_CAPTURE);
        $this->assertEquals(1, $test);
        $this->assertEquals('aaa', $matches[1][0]);
        $this->assertEquals('bbb', $matches[2][0]);

        $test = preg_match('/\/content\/([-a-zA-Z0-9_]*)\/([\p{L})-_\.]+)/ui', '/content/aaa/ààà', $matches, PREG_OFFSET_CAPTURE);
        $this->assertEquals(1, $test);
        $this->assertEquals('aaa', $matches[1][0]);
        $this->assertEquals('ààà', $matches[2][0]);

        $test = preg_match('/\/content\/([-a-zA-Z0-9_]*)\/([\p{L})-_\.]+)/ui', '/content/aaa/ààà', $matches, PREG_OFFSET_CAPTURE);
        $this->assertEquals(1, $test);
        $this->assertEquals('aaa', $matches[1][0]);
        $this->assertEquals('ààà', $matches[2][0]);

        $test = preg_match('/\/content\/([-a-zA-Z0-9_]*)\/([\p{L})-_\.]+)/ui', '/content/aaa/a_b-c.json', $matches, PREG_OFFSET_CAPTURE);
        $this->assertEquals(1, $test);
        $this->assertEquals('aaa', $matches[1][0]);
        $this->assertEquals('a_b-c.json', $matches[2][0]);

        $endpoint = 'content';
        // Regex example : /content/calendar/some-accents_àéü.json
        $test = preg_match('/\/' . $endpoint . '\/([-a-zA-Z0-9_]*)\/([\p{L})-_\.]+)/ui', '/content/aaa/a_b-c.json', $matches, PREG_OFFSET_CAPTURE);
        $this->assertEquals(1, $test);
        $this->assertEquals('aaa', $matches[1][0]);
        $this->assertEquals('a_b-c.json', $matches[2][0]);
    }
}
