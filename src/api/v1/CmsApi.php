<?php
require_once 'utils/SecureRestApi.php';
require_once 'utils/ContentService.php';
/*
* /api/v1/content/cake?filter=foobar
*/
class CmsApi extends SecureRestApi
{
    const INDEX_JSON = '/index/index.json';

    const REQUESTBODY = 'requestbody';


    public function __construct($conf)
    {
        parent::__construct($conf);
    }

    /**
     * /api/v1/content/list
     */
     protected function list()
     {
         $this->checkConfiguration();

         $datatype = $this->getDataType();


         if ($this->method === 'GET') {
             $service = new ContentService($this->conf->{'publicdir'});
             $response = $service->getAll($datatype . CmsApi::INDEX_JSON);

             return $response->getResult();
         } elseif ($this->method === 'POST') {
         }
     }


     /**
      * /api/v1/content/save
      */
     protected function save()
     {
         echo '!!!!!!!!!!!!!!!!!!';
         $this->checkConfiguration();

         $datatype = $this->getDataType();

         if ($this->method === 'POST') {
             $service = new ContentService($this->conf->{'publicdir'});

             echo '!!!!!!!!!!!!!!!!!!' . $this->request['requestbody'];

             $response = $service->post( $datatype, 'id', $this->request['requestbody']);

             return $response->getResult();
         }
     }


    private function getDataType() : string
    {
        $datatype = null;
        if (isset($this->verb)) {
            $datatype = $this->verb;
        }
        if (!isset($datatype)) {
            throw new Exception('Empty datatype');
        }
        return $datatype;
    }

    private function checkConfiguration()
    {
        if (!isset($this->conf->{'publicdir'})) {
            throw new Exception('Empty publicdir');
        }
    }
}
