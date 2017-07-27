<?php

require_once 'SecureRestApi.php';

/*
 * File API with authentication.
 * Basic file upload using _FILES
 */
class FileApi extends SecureRestApi
{

      const REQUESTBODY = 'requestbody';

    /**
   * media directory (eg: media ).
   */
  private $media;

    private $homedir;

  /**
   * media directory (eg: /var/www/html/media ).
   */
  private $mediadir;

    public function __construct($conf)
    {
        parent::__construct($conf);

        // Default headers for RESTful API
        if ($this->enableHeaders) {
            header('Access-Control-Allow-Methods: *');
        }
        $this->homedir = $this->conf->{'homedir'};

        $this->media = $this->conf->{'media'};

        $this->mediadir = $this->conf->{'homedir'}.'/'.$this->media;
    }

    /**
     * basic file upload.
     */
    protected function basicupload()
    {
        $response = new Response();
        $response->setCode(400);
        $response->setMessage('Bad parameters');
        $response->setResult('{}');

        try {
            $this->checkConfiguration();

            $datatype = $this->getDataType();

          //
          // Preflight requests are send by Angular
          //
          if ($this->method === 'OPTIONS') {
              // eg : /api/v1/content
              $response = $this->preflight();
          }

          //
          if (isset($datatype) && strlen($datatype) > 0) {
              // eg : /api/v1/content/calendar
              if ($this->method === 'GET') {
                  // TODO get file
              } elseif ($this->method === 'POST') {
                  if (array_key_exists(0, $this->args)) {
                      //get the full data of a single record $this->args contains the remaining path parameters
                    // eg : /api/v1/file/calendar/1
                    $uploadResult = $this->uploadFiles($datatype, $this->args[0]);
                      $response->setCode(200);
                      $response->setMessage('');
                      $response->setResult(json_encode($uploadResult));
                  }
              }
          }
        } catch (Exception $e) {
            $response->setCode(520);
            $response->setMessage($e->getMessage());
            $response->setResult($this->errorToJson($e->getMessage()));
        } finally {
            return $response;
        }
    }

    /**
    * Sample request body :
    * [{ "url": "http://wwww.example.com/foobar.pdf", "title":"Foobar.pdf"}]
    */
    protected function download()
    {
        $response = new Response();
        $response->setCode(400);
        $response->setMessage('Bad parameters');
        $response->setResult('{}');

        try {
            $this->checkConfiguration();

            $datatype = $this->getDataType();

          //
          // Preflight requests are send by Angular
          //
          if ($this->method === 'OPTIONS') {
              // eg : /api/v1/content
              $response = $this->preflight();
          }

          //
          if (isset($datatype) && strlen($datatype) > 0) {
              // eg : /api/v1/content/calendar
              if ($this->method === 'GET') {
                  // TODO get file
              } elseif ($this->method === 'POST') {
                  if (array_key_exists(0, $this->args)) {

                    $uploadResult = $this->downloadFiles($datatype, $this->args[0], urldecode($this->request[self::REQUESTBODY]));
                      $response->setCode(200);
                      $response->setMessage('');
                      $response->setResult(json_encode($uploadResult));
                  }
              }
          }
        } catch (Exception $e) {
            $response->setCode(520);
            $response->setMessage($e->getMessage());
            $response->setResult($this->errorToJson($e->getMessage()));
        } finally {
            return $response;
        }
    }

    private function uploadFiles($type, $id)
    {
        /*
      File properties example
      - name:1.jpg
      - type:image/jpeg
      - tmp_name:/tmp/phpzDc6qT
      - error:0
      - size:700
        */
      $result = json_decode('[]');
        foreach ($_FILES as $formKey => $file) {

            // media/calendar/1
            $uridir = $this->media.'/'.$type.'/'.$id;

            // /var/www/html/media/calendar/1
            $destdir = $this->homedir.'/'.$uridir;

            // create directory if it doesn't exist
            if (!file_exists($destdir)) {
                mkdir($destdir, 0777, true);
            }

            // upload
            if (isset($file['tmp_name']) && isset($file['name'])) {
                $destfile = $destdir.'/'.$file['name'];
                if (move_uploaded_file($file['tmp_name'], $destfile)) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE); // get mime type
                $mimetype = finfo_file($finfo, $destfile);
                    finfo_close($finfo);

                    $fileResult = json_decode('{}');
                    $fileResult->{'title'} = $file['name'];
                    $fileResult->{'url'} = '/'.$uridir.'/'.$file['name'];
                    $fileResult->{'size'} = $file['size'];
                    $fileResult->{'mimetype'} = $mimetype;
                    array_push($result, $fileResult);
                } else {
                    throw new Exception($file['name'].' KO');
                }
            }
        }

        if (count($result) === 0) {
            throw new Exception('no files');
        }

        return $result;
    }


    private function downloadFiles($type, $id, $filesStr)
    {
      $response = new Response();
      $response->setCode(400);
      $response->setMessage('Bad parameters');
      $response->setResult('{}');

      $files = json_decode($filesStr);

      $result = json_decode('[]');
        foreach ($files as $formKey => $file) {

            // media/calendar/1
            $uridir = $this->media.'/'.$type.'/'.$id;

            // /var/www/html/media/calendar/1
            $destdir = $this->homedir.'/'.$uridir;

            // create directory if it doesn't exist
            if (!file_exists($destdir)) {
                mkdir($destdir, 0777, true);
            }


            // upload
            if (isset($file->{'url'})) {
                $current = file_get_contents($file->{'url'});
                $destfile = $destdir.'/'.$file->{'title'};

                if (file_put_contents($destfile, $current)) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE); // get mime type
                    $mimetype = finfo_file($finfo, $destfile);
                    finfo_close($finfo);

                    $filesize = filesize($destfile);

                    $fileResult = json_decode('{}');
                    $fileResult->{'title'} = $file->{'title'};
                    $fileResult->{'url'} = '/'.$uridir.'/'.$file->{'title'};
                    $fileResult->{'size'} = $filesize;
                    $fileResult->{'mimetype'} = $mimetype;
                    array_push($result, $fileResult);
                } else {
                    throw new Exception($file['name'].' KO');
                }
            }
        }

        if (count($result) === 0) {
            throw new Exception('no files');
        }

        $response->setResult(json_encode($result));
        $response->setCode(200);
        return $response;
    }

    private function getDataType(): string
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
        if (!isset($this->conf->{'homedir'})) {
            throw new Exception('Empty publicdir');
        }
    }


    /**
 * http://stackoverflow.com/questions/25727306/request-header-field-access-control-allow-headers-is-not-allowed-by-access-contr.
 */
public function preflight(): Response
{
  $response = new Response();
  $response->setCode(200);
  $response->setResult('{}');

  header('Access-Control-Allow-Methods: GET,PUT,POST,DELETE,OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');


    return $response;
}
}
