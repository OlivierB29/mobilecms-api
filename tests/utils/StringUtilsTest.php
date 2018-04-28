<?php

declare(strict_types=1);
namespace mobilecms\utils;

use PHPUnit\Framework\TestCase;

final class StringUtilsTest extends TestCase
{


    public function testStartsWith()
    {
      $this->assertTrue(StringUtils::startsWith('foobar', 'foo'));
      $this->assertFalse(StringUtils::startsWith('foobar', 'bar'));
    }

    public function testEndsWith()
    {
      $this->assertTrue(StringUtils::endsWith('foobar', 'bar'));
      $this->assertFalse(StringUtils::endsWith('foobar', 'foo'));
    }

}
