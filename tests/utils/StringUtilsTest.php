<?php

declare(strict_types=1);
namespace mobilecms\utils;

use PHPUnit\Framework\TestCase;

final class StringUtilsTest extends TestCase
{
    public function testStartsWith()
    {
        $this->assertTrue(\mobilecms\utils\StringUtils::startsWith('foobar', 'foo'));
        $this->assertFalse(\mobilecms\utils\StringUtils::startsWith('foobar', 'bar'));
    }

    public function testEndsWith()
    {
        $this->assertTrue(\mobilecms\utils\StringUtils::endsWith('foobar', 'bar'));
        $this->assertFalse(\mobilecms\utils\StringUtils::endsWith('foobar', 'foo'));
    }
}
