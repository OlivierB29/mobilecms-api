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
    const FILE = 'file';
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
        //
        if (isset($datatype) && strlen($datatype) > 0) {
            //eg : /api/v1/content/calendar
            if ($this->method === 'GET') {

                //$this->args contains the remaining path parameters
                //eg : /api/v1/content/calendar/1/foo/bar
                // ['1', 'foo', 'bar']
                if (array_key_exists(0, $this->args)) {
                    //this

                    $response = $service->getRecord($datatype, $this->args[0]);

                    return $response->getResult();
                } else {
                    $response = $service->getAllObjects($datatype);

                    return $response->getResult();
                }
            } elseif ($this->method === 'POST') {

                //eg : /api/v1/content/calendar

                $response = $service->post($datatype, CmsApi::ID, $this->request ['requestbody']);

                $debug = json_decode("{}");
                $debug->{'msg'} = $response->getMessage();
                return json_encode($debug);
            }
        } else {
            if ($this->method === 'GET' || $this->method === 'OPTIONS') {
                //eg : /api/v1/content
                return $service->options('types.json');
            }
        }
    }

    protected function file()
    {
        $this->checkConfiguration();


        $service = new ContentService($this->conf->{'publicdir'});

            //eg : /api/v1/file?filename
            if ($this->method === 'GET') {

                //$this->args contains the remaining path parameters
                //eg : /api/v1/file?file=/calendar/1/foo/bar/sample.json


                if (array_key_exists(CmsApi::FILE, $this->getRequest())) {
                    //this

                    $response = $service->getFilePath($this->getRequest()[CmsApi::FILE]);
                    if($response->getCode() === 200) {
                      
                      return file_get_contents($response->getResult());
                    } else {
                      return $response->getMessage();
                    }

                } else {
                  return "";
                }
            } else {
              throw new Exception("bad request");
            }

    }


    private function getDataType(): string
    {
        $datatype = null;
        if (isset($this->verb)) {
            $datatype = $this->verb;
        }
        if (! isset($datatype)) {
            throw new Exception('Empty datatype');
        }
        return $datatype;
    }
    private function checkConfiguration()
    {
        if (! isset($this->conf->{'publicdir'})) {
            throw new Exception('Empty publicdir');
        }
    }

    /**
    * http://stackoverflow.com/questions/25727306/request-header-field-access-control-allow-headers-is-not-allowed-by-access-contr
    */
    public function options()
    {
        header("Access-Control-Allow-Methods: *");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    }
}
