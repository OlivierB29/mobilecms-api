<?php
declare(strict_types=1);
include 'conf.php';

use PHPUnit\Framework\TestCase;

final class CmsApiTest extends TestCase
{

  private $token;

  private $conf;

 protected function setUp()
 {

   $this->conf = json_decode('{"enableheaders" : "false", "publicdir":"'.HOME.'/tests-data/public", "privatedir":"'.HOME.'/tests-data/private" , "apikeyfile" : "tests-data/private/apikeys/key1.json" }');



   $service = new UserService('tests-data/userservice');
   $response = $service->getToken('test@example.com', 'Sample#123456');

   $this->token = 'Bearer ' . $response->getResult();
 }

 public function testOptions()
 {
     $path = '/api/v1/content';

     $headers = ['Authorization' => $this->token, 'apiKey' => '123'];

     $REQUEST = ['path' => $path];

     $SERVER = [
     'REQUEST_URI' => $path,
     'REQUEST_METHOD' => 'GET',
     'HTTP_ORIGIN' => 'foobar'
   ];

     $GET = [ 'requestbody' => '{}'];
     $POST = null;

     $API = new CmsApi($this->conf);
     $API->setRequest($REQUEST, $SERVER, $GET, $POST);
     $API->authorize($headers, $SERVER);
     $result = $API->processAPI();

     $this->assertTrue(
       $result != null && $result != ''
     );
 }


    public function testGet1()
    {
        $path = '/api/v1/content/calendar';
        $headers = ['Authorization' => $this->token,
                    'apiKey' => '123'];


        $REQUEST = ['path' => $path];




        $SERVER = [
        'REQUEST_URI' => $path,
        'REQUEST_METHOD' => 'GET',
        'HTTP_ORIGIN' => 'foobar'
      ];



        $GET = [
        'requestbody' => '{}'
        ];
        $POST = null;


        $API = new CmsApi($this->conf);
        $API->setRequest($REQUEST, $SERVER, $GET, $POST);

        $API->authorize($headers, $SERVER);

        $result = $API->processAPI();
        echo $result;

        $this->assertTrue(
          $result != null && $result != ''
        );
    }

    public function testPost1()
    {
      $path = '/api/v1/content/calendar';
      $recordStr = '{"id":"10","type" : "calendar","date":"201509","activity":"activitya","title":"some seminar of activity A","organization":"Some org","description":"some infos","url":"","location":"","startdate":"","enddate":"","updated":"","updatedby":""}';

      $REQUEST = ['path' => $path];



      $headers = ['Authorization' => $this->token,
                  'apiKey' => '123'];

        $SERVER = [
        'REQUEST_URI' => $path,
        'REQUEST_METHOD' => 'POST',
        'HTTP_ORIGIN' => 'foobar'

      ];

        $GET = null;
        $POST = [
        'requestbody' => $recordStr
        ];


        $API = new CmsApi($this->conf);

        $API->setRequest($REQUEST, $SERVER, $GET, $POST);

        $API->authorize($headers, $SERVER);

        $result = $API->processAPI();

        $this->assertTrue(
          $result != null && $result != ''
        );
    }
}
