<?php

declare(strict_types=1);
namespace mobilecms\utils;

use PHPUnit\Framework\TestCase;

final class PropertiesTest extends TestCase
{

    public function testConf()
    {
        $u = new Properties();
        $u->loadConf('tests/conf.json');

        $this->assertTrue("sendmail@example.org" == $u->getString('mailsender'));
    }
}
