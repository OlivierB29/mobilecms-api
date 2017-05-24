<?php

require_once 'Response.php';
require_once 'JsonUtils.php';

function compareIndex($key)
{
    return function ($a, $b) use ($key) {
        return strnatcmp($a->{$key}, $b->{$key});
    };
}

/**
 * Read and save data from JSON files.
 * Future plans : consider http://stackoverflow.com/questions/13899342/can-we-use-json-as-a-database
 * Public methods :
 * - getAll : get all elements
 * - get : return a single element
 * - put : create a new item
 * - post : update an existing item.
 */
class ContentService
{
    /**
     * create backup of history file.
     */
    private $enableIndexHistory = false;

    /**
     * main directory (eg: /opt/foobar/data ).
     */
    private $databasedir;

    /**
     * constructor.
     */
    public function __construct($databasedir)
    {
        $this->databasedir = $databasedir;
    }

    /**
     * Get a single record.
     *
     * $type eg : calendar
     * $keyname : unique id
     * $keyvalue : id value, eg :1
     */
    public function getRecord(string $type, string $keyvalue)
    {
        $response = new Response();
        $response->setCode(400);
        $response->setMessage('Bad parameters');
        $response->setResult('{}');

        try {

            // Read the JSON file
            $file = $this->databasedir.'/'.$type.'/'.$keyvalue.'.json';

            // get one element
            if (file_exists($file)) {
                $response->setResult(file_get_contents($file));
                $response->setCode(200);
            } else {
                $response->appendMessage('not found '.$keyname.' : '.$keyvalue);
                $response->setCode(404);
            }
        } catch (Exception $e) {
            $response->setCode(520);
            $response->setMessage($e->getMessage());
        } finally {
            return $response;
        }
    }

    public function deleteRecord(string $type, string $keyvalue)
    {
        $response = new Response();
        $response->setCode(400);
        $response->setMessage('Bad parameters');
        $response->setResult('{}');

        try {

            // Read the JSON file
            $file = $this->databasedir.'/'.$type.'/'.$keyvalue.'.json';
            $destdir = $this->databasedir.'/'.$type.'/archives';
            $destfile = $destdir . '/' . $keyvalue.'.json';

                
                unlink($file);

                $response->setCode(200);
            } else {
                $response->appendMessage('not found '.$keyname.' : '.$keyvalue);
                $response->setCode(404);
            }
        } catch (Exception $e) {
            $response->setCode(520);
            $response->setMessage($e->getMessage());
        } finally {
            return $response;
        }
    }

    /**
     * Return a filepath from a single filename, only contained in the public databasedir.
     * Valid path :
     *	calendar/1.json , new/foobar.json, index/index.json , ...
     * Invalid path :
     * /var/www/private/somefile.sh.
     *
     * $filename : calendar/1.json , new/foobar.json, index/index.json , ...
     *
     * @return calendar/index/index.json
     */
    public function getFilePath(string $filename): Response
    {
        $response = new Response();
        $response->setCode(400);
        $response->setMessage('Bad parameters');
        $response->setResult('{}');

        try {

            //
            //forbid upper directory
            //
            if (strpos($filename, '..') !== false) {
                throw new Exception('Invalid path '.$filename, 1);
            }

            $file = $this->databasedir.'/'.$filename;

            // get one element
            if (file_exists($file)) {
                $response->setResult($file);
                $response->setCode(200);
            } else {
                $response->appendMessage('not found '.$file);
                $response->setCode(404);
            }
        } catch (Exception $e) {
            $response->setCode(520);
            $response->setMessage($e->getMessage());
        } finally {
            return $response;
        }
    }

    /**
     * return a single element, from a JSON array stored in file.
     * $filename : JSON data filename eg: [{"id":"1", "foo":"bar"}, {"id":"2", "foo":"bar2"}]
     * $keyname : primary key inside the file eg : id
     * $keyvalue : eg : 1.
     *
     * @return : Response object with a JSON object eg : {"id":"1", "foo":"bar"}
     */
    public function get(string $filename, string $keyname, string $keyvalue)
    {
        $response = new Response();
        $response->setCode(400);
        $response->setMessage('Bad parameters');
        $response->setResult('{}');

        try {

            // Read the JSON file
            $file = $this->databasedir.'/'.$filename;
            $data = JsonUtils::readJsonFile($file);

            // get one element
            if (isset($keyvalue)) {

                // extract element data
                $existingObject = JsonUtils::getByKey($data, $keyname, $keyvalue);
                if (isset($existingObject)) {
                    $response->setResult(json_encode($existingObject));
                    $response->setCode(200);
                } else {
                    // element not found
                    $response->appendMessage('not found '.$keyname.' : '.$keyvalue);
                    $response->setCode(404);
                }
            } else {
                // return all
                $response->setResult(json_encode($data));
                $response->setCode(200);
            }
        } catch (Exception $e) {
            $response->setCode(520);
            $response->setMessage($e->getMessage());
        } finally {
            return $response;
        }
    }

    /**
     * get all JSON files list of a directory
     * eg: [{"id":"1", "filename": "1.json"}, {"id":"2", "filename": "2.json"}].
     */
    public function getAllObjects($type)
    {
        $response = new Response();
        $response->setCode(400);
        $response->setMessage('Bad parameters');
        $response->setResult('{}');
        $thelist = [];
        try {
            if ($handle = opendir($this->databasedir.'/'.$type)) {
                while (false !== ($file = readdir($handle))) {
                    $fileObject = json_decode('{}');
                    if ($file != '.' && $file != '..' && strtolower(substr($file, strrpos($file, '.') + 1)) == 'json') {
                        // echo $file;
                        $fileObject->{'filename'} = $file;
                        $fileObject->{'id'} = str_replace('.json', '', $file);
                        array_push($thelist, $fileObject);
                    }
                }
                closedir($handle);
            }

            $response->setResult(json_encode($thelist));
        } catch (Exception $e) {
            $response->setCode(520);
            $response->setMessage($e->getMessage());
            $response->setResult('{ "result":"'.$e->getMessage().'"}');
        } finally {
            return $response;
        }
    }

    /**
     * get all elements from an array, contained in a single file
     * $filename : JSON data filename eg: [{"id":"1", "foo":"bar"}, {"id":"2", "foo":"bar2"}].
     *
     * @return : Response object with a JSON array
     */
    public function getAll(string $filename)
    {
        $response = new Response();
        $response->setCode(400);
        $response->setMessage('Bad parameters');
        $response->setResult('{}');

        try {

            // Read the JSON file
            $file = $this->databasedir.'/'.$filename;
            $data = JsonUtils::readJsonFile($file);
            if (isset($data)) {
                $response->setCode(200);
                $response->setResult(json_encode($data));
            }
        } catch (Exception $e) {
            $response->setCode(520);
            $response->setMessage($e->getMessage());
            $response->setResult('{ "result":"'.$e->getMessage().'"}');
        } finally {
            return $response;
        }
    }

    /**
     * create a single element
     * $type : object type (eg : calendar)
     * $filename : JSON data filename
     * $keyname : primary key inside the file.
     *
     * @return : Response object with a JSON object
     */
    public function put(string $type, string $keyname, string $recordStr)
    {
        // TODO : throw an exception if existing file
        return $this->post($type, $keyname, $recordStr);
    }

    /**
     * return a record file path
     * $type : name of type eg : calendar
     * $id : unique id of record eg : 1.
     *
     * @return /foobar/calendar/index.json
     */
    private function getItemFileName(string $type, string $id) : string
    {
        if (!isset($type)) {
            throw new Exception('empty type', 1);
        }
        if (!isset($id)) {
            throw new Exception('empty id', 1);
        }

        return $this->databasedir.'/'.$type.'/'.$id.'.json';
    }

    /**
     * return an index file path
     * $type : eg : calendar.
     *
     * @return /foobar/calendar/index.json
     */
    public function getIndexFileName(string $type) : string
    {
        if (!isset($type)) {
            throw new Exception('empty type', 1);
        }

        return $this->databasedir.'/'.$type.'/index/index.json';
    }

    /**
     * return an template index file path
     * $type : eg : calendar.
     *
     * @return /foobar/calendar/index.json
     */
    private function getIndexTemplateFileName(string $type) : string
    {
        if (!isset($type)) {
            throw new Exception('empty type', 1);
        }

        return $this->databasedir.'/'.$type.'/index/index_template.json';
    }

    public function post(string $type, string $keyname, string $recordStr)
    {
        $response = new Response();
        $response->setCode(400);
        $response->setMessage('Bad parameters');
        $response->setResult('{}');

        if (isset($recordStr)) {

            // Decode JSON
            $myobjectJson = json_decode($recordStr);
            $response->setResult($recordStr);
            unset($recordStr);

            // detect id
            $id = $myobjectJson->{$keyname};

            // file name
            $file = $this->getItemFileName($type, $id);

            // write to file
            JsonUtils::writeJsonFile($file, $myobjectJson);
            unset($myobjectJson);
            $response->setCode(200);
            $response->setMessage('OK');
        } else {
            $response->setMessage('Bad parameters object');
        }

        return $response;
    }

    /**
     * Add object id to index.
     */
    public function publishById(string $type, string $keyname, string $keyvalue)
    {
        $response = new Response();
        $response->setCode(400);
        $response->setMessage('Bad parameters');
        $response->setResult('{}');

        try {
            // file name eg: index.json
            $response->setMessage('getIndexFileName');
            $file = $this->getIndexFileName($type);

            // create a backup of previous index file eg: history/index-TIMESTAMP.json
            if ($this->enableIndexHistory) {
                $backupDone = $this->mycopy($file, $this->getBackupIndexFileName($type));
                $response->setMessage('backup '.$backupDone);
            }

            /*
            Load a template for index.
            eg :
                { "id": "", "date": "",  "activity": "", "title": "" }
            */
            $response->setMessage('getIndexTemplateFileName'.$this->getIndexTemplateFileName($type));
            $indexValue = JsonUtils::readJsonFile($this->getIndexTemplateFileName($type));

            // Read the full JSON record

            $recordFile = $this->databasedir.'/'.$type.'/'.$keyvalue.'.json';
            $response->setMessage('readJsonFile'.$recordFile);
            $record = JsonUtils::readJsonFile($recordFile);

            //
            //copy some fields to index
            //
            $response->setMessage('copy values to index');
            JsonUtils::copy($record, $indexValue);
            unset($record);

            // get index data
            $response->setMessage('get index data');
            $data = JsonUtils::readJsonFile($file);

            $response->setMessage('put');
            $data = JsonUtils::put($data, $keyname, $indexValue);

            // write to file
            $response->setMessage('write to file');
            JsonUtils::writeJsonFile($file, $data);
            unset($data);
            $response->setMessage('done');
            $response->setCode(200);
        } catch (Exception $e) {
            $response->setCode(520);
            $response->setMessage($e->getMessage());
        } finally {
            return $response;
        }
    }

    public function rebuildIndex(string $type, string $keyname)
    {
        $response = new Response();
        $response->setCode(400);
        $response->setMessage('Bad parameters');
        $response->setResult('{}');

        $data = [];
          // file name eg: index.json
          $response->setMessage('getIndexFileName');
        $indexFile = $this->getIndexFileName($type);

          /*
          Load a template for index.
          eg :
              { "id": "", "date": "",  "activity": "", "title": "" }
          */
          $response->setMessage('getIndexTemplateFileName'.$this->getIndexTemplateFileName($type));
        $indexTemplate = JsonUtils::readJsonFile($this->getIndexTemplateFileName($type));

        try {
            if ($handle = opendir($this->databasedir.'/'.$type)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != '.' && $file != '..' && strtolower(substr($file, strrpos($file, '.') + 1)) == 'json') {
                        // Read the full JSON record
                      $record = JsonUtils::readJsonFile($this->databasedir.'/'.$type.'/'.$file);

                      //
                      //copy some fields to index
                      //
                      $indexValue = clone $indexTemplate;
                        $response->setMessage('copy values to index');
                        JsonUtils::copy($record, $indexValue);
                        unset($record);
                        $response->setMessage('put');
                        array_push($data, $indexValue);
                        unset($indexValue);
                    }
                }
                closedir($handle);
            }

            //sort
            $response->setMessage('sort');
            usort($data, compareIndex($keyname));

            // write to file

            $response->setMessage('write to file');
            JsonUtils::writeJsonFile($indexFile, $data);
            unset($data);
            $response->setMessage('done');
            $response->setCode(200);
        } catch (Exception $e) {
            $response->setCode(520);
            $response->setMessage($e->getMessage());
        } finally {
            return $response;
        }
    }

    /**
     * generate a backup index file name.
     */
    private function getBackupIndexFileName(string $type) : string
    {
        if (!isset($type)) {
            throw new Exception('empty type', 1);
        }

        return $this->databasedir.'/'.$type.'/history/index-'.time().'.json';
    }

    /**
     * copy a file and create directory if necessary.
     */
    private function mycopy(string $s1, string $s2)
    {
        $path = pathinfo($s2);
        if (!file_exists($path['dirname'])) {
            mkdir($path['dirname'], 0777, true);
        }
        if (!copy($s1, $s2)) {
            throw new Exception('copy failed ', 1);
        }
    }

    /**
     * returns options files content.
     */
    public function options(string $filename)
    {
        $file = $this->databasedir.'/'.$filename;

        return json_encode(JsonUtils::readJsonFile($file));
    }
}
