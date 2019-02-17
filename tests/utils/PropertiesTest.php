<?php

declare(strict_types=1);
namespace mobilecms\utils;

use PHPUnit\Framework\TestCase;

final class PropertiesTest extends TestCase
{
    private $conf;

    protected function setUp(): void
    {
        $this->conf = new Properties();
        $this->conf->loadConf('tests-data/properties/conf.json');
    }

    public function testConf()
    {
        $this->assertTrue("bar" == $this->conf->getString('foo'));
    }

    public function testEmptyConf()
    {
        $this->expectException(\Exception::class);
        $this->conf = new Properties();
        $this->conf->loadConf('tests/empty.json');
    }

    public function testInteger1()
    {
        $this->assertTrue(123 === $this->conf->getInteger('int1', -1));
    }

    public function testInteger2()
    {
        $this->assertTrue(456 === $this->conf->getInteger('int2', -1));
    }
}
