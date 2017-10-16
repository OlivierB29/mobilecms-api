<?php

declare(strict_types=1);
namespace mobilecms\api;

use PHPUnit\Framework\TestCase;

final class AdminApiTest extends TestCase
{
    private $user;
    private $token;
    private $conf;

    private $guest;
    private $guesttoken;

    private $memory1 = 0;
    private $memory2 = 0;

    protected function setUp()
    {
        $this->memory1 = 0;
        $this->memory2 = 0;

        $this->conf = json_decode(file_get_contents('tests/conf.json'));

        $service = new \mobilecms\utils\UserService(realpath('tests-data') . $this->conf->{'privatedir'} . '/users');

        $response = $service->getToken('admin@example.com', 'Sample#123456');
        $this->user = json_decode($response->getResult());
        $this->token = 'Bearer ' . $this->user->{'token'};

        $response = $service->getToken('guest@example.com', 'Sample#123456');
        $this->guest = json_decode($response->getResult());
        $this->guesttoken = 'Bearer ' . $this->guest->{'token'};

        $response = $service->getToken('editor@example.com', 'Sample#123456');
        $this->editor = json_decode($response->getResult());
        $this->editortoken = 'Bearer ' . $this->guest->{'token'};

        $this->memory();
    }

    private function memory()
    {
        $this->memory1 = $this->memory2;

        $this->memory2 = memory_get_usage();

        return $this->memory2 - $this->memory1;
    }

    public function testUpdate()
    {
        $email = 'role@example.com';
        $path = '/adminapi/v1/content/users/' . $email;

        $API = new AdminApi($this->conf);
        $API->setRootDir(realpath('tests-data'));
        $file = $API->getPrivateDirPath() . '/users/' . $email . '.json';
        $this->assertTrue(copy($API->getPrivateDirPath() . '/save/' . $email . '.json', $file));

        $headers = ['Authorization' => $this->token];
        $REQUEST = [];
        $SERVER = ['REQUEST_URI' => $path, 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'foobar'];
        $GET = null;

        $recordStr = '{ "name": "test role", "email": "' . $email . '", "role":"editor"}';
        $POST = ['requestbody' => $recordStr];

        $API->setRequest($REQUEST, $SERVER, $GET, $POST, $headers);
        $response = $API->processAPI();

        $result = $response->getResult();

        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($result != null && $result != '');
        $this->assertTrue(file_exists($file));

        if (file_exists($file)) {
            unlink($file);
        }
    }
}
