<?php

declare(strict_types=1);
namespace mobilecms\utils;

use PHPUnit\Framework\TestCase;

final class MailUtilsTest extends TestCase
{
    public function testHeaders()
    {
        $u = new MailUtils('');
        $result = $u->getHeaders('foo@bar.org');
        $this->assertTrue(strpos($result, 'MIME-Version: 1.0') !== false);
        $this->assertTrue(strpos($result, 'Content-Type: text/html; charset=UTF-8') !== false);
        $this->assertTrue(strpos($result, 'From: foo@bar.org') !== false);
    }
}
