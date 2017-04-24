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

    const ID = 'id';

    const TYPE = 'type';


    public function __construct($conf)
    {
        parent::__construct($conf);
    }

    /**
     * /api/v1/content
     */
     protected function content()
     {
         $this->checkConfiguration();

         $datatype = $this->getDataType();
         $service = new ContentService($this->conf->{'publicdir'});
         if(isset($datatype) && strlen($datatype) >0 ) {
           if ($this->method === 'GET') {

               $response = $service->getAll($datatype . CmsApi::INDEX_JSON);

               return $response->getResult();
           } elseif ($this->method === 'POST') {


               $response = $service->post($datatype, CmsApi::ID, $this->request['requestbody']);

               return $response->getResult();
           }
         } else {
           if ($this->method === 'GET') {
             return $service->options('types.json');
           }
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
