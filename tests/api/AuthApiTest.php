<?php
namespace mobilecms\api;

abstract class AuthApiTest extends ApiTest
{
    protected $user;
    protected $token;
    protected $conf;

    protected $guest;
    protected $guesttoken;

    protected $editor;
    protected $editortoken;

    protected $admin;
    protected $admintoken;

    protected function setUp()
    {
        parent::setUp();
        $this->memory1 = 0;
        $this->memory2 = 0;

        $this->conf = json_decode(file_get_contents('tests/conf.json'));

        $service = new \mobilecms\utils\UserService(realpath('tests-data') . $this->conf->{'privatedir'} . '/users');

        $response = $service->getToken('editor@example.com', 'Sample#123456');
        $this->user = json_decode($response->getResult());
        $this->token = 'Bearer ' . $this->user->{'token'};

        $response = $service->getToken('guest@example.com', 'Sample#123456');
        $this->guest = json_decode($response->getResult());
        $this->guesttoken = 'Bearer ' . $this->guest->{'token'};

        $response = $service->getToken('editor@example.com', 'Sample#123456');
        $this->editor = json_decode($response->getResult());
        $this->editortoken = 'Bearer ' . $this->guest->{'token'};

        $response = $service->getToken('admin@example.com', 'Sample#123456');
        $this->admin = json_decode($response->getResult());
        $this->admintoken = 'Bearer ' . $this->user->{'token'};

        $this->memory();

        // $this->headers=['Authorization'=>$this->token];
    }

    protected function setGuest()
    {
        $this->headers=['Authorization' => $this->guesttoken];
    }

    protected function setEditor()
    {
        $this->headers=['Authorization' => $this->editortoken];
    }

    protected function setAdmin()
    {
        $this->headers=['Authorization' => $this->admintoken];
    }
}
