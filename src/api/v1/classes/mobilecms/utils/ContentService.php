<?php namespace mobilecms\utils;

/**
 * Function used for sorting.
 *
 * @param key $key name
 */
function compareIndex(string $key)
{
    /*
    * compare two object using the $key property
    * @param a first object to compare
    * @param b second object to compare
    */
    return function (\stdClass $a, \stdClass $b) use ($key) {
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
     * Create backup of history file.
     */
    private $enableIndexHistory = false;

    /**
     * Main directory (eg: /opt/foobar/data ).
     */
    private $databasedir;

    /**
     * Constructor.
     *
     * @param databasedir $databasedir eg : public
     */
    public function __construct(string $databasedir)
    {

        $this->databasedir = $databasedir;
    }

    /**
     * Return a record file path.
     *
     * @param string $type : name of type eg : calendar
     * @param string $id   : unique id of record eg : 1
     *
     * @return /foobar/calendar/index.json
     */
    private function getItemFileName(string $type, string $id) : string
    {
        if (!isset($type)) {
            throw new \Exception('empty type', 1);
        }
        if (!isset($id)) {
            throw new \Exception('empty id', 1);
        }

        return $this->databasedir . '/' . $type . '/' . $id . '.json';
    }

    /**
     * Return a template index file path.
     *
     * @param string $type : name of type eg : calendar
     *
     * @return /foobar/calendar/index.json
     */
    private function getIndexTemplateFileName(string $type) : string
    {
        if (!isset($type)) {
            throw new \Exception('empty type', 1);
        }

        return $this->databasedir . '/' . $type . '/index/index_template.json';
    }

    /**
     * Generate a backup index file name.
     *
     * @param string $type : eg calendar
     *
     * @return file name
     */
    private function getBackupIndexFileName(string $type) : string
    {
        if (!isset($type)) {
            throw new \Exception('empty type', 1);
        }

        return $this->databasedir . '/' . $type . '/history/index-' . time() . '.json';
    }


    /**
     * Copy a file and create directory if necessary.
     *
     * @param string $s1 : source
     * @param string $s2 : dest
     */
    private function mycopy(string $s1, string $s2)
    {
        $path = pathinfo($s2);
        if (!file_exists($path['dirname'])) {
            mkdir($path['dirname'], 0777, true);
        }
        if (!copy($s1, $s2)) {
            throw new \Exception('copy failed ', 1);
        }
    }


    /**
     * Get a single record.
     *
     * @param string $type     eg: calendar
     * @param string $keyvalue : id value, eg :1
     */
    public function getRecord(string $type, string $keyvalue)
    {
        $response = $this->getDefaultResponse();

        // Read the JSON file
        $file = $this->databasedir . '/' . $type . '/' . $keyvalue . '.json';

        // get one element
        if (file_exists($file)) {
            $response->setResult(file_get_contents($file));
            $response->setCode(200);
        } else {
            $response->setError(404, 'not found ' . $type . '/' . $keyvalue);
        }

        return $response;
    }

    /**
     * Delete a record.
     *
     * @param string $type     eg: calendar
     * @param string $keyvalue : id value, eg :1
     */
    public function deleteRecord(string $type, string $keyvalue)
    {
        $response = $this->getDefaultResponse();

        // Read the JSON file
        $file = $this->databasedir . '/' . $type . '/' . $keyvalue . '.json';

        if (file_exists($file)) {
            unlink($file);

            $response->setCode(200);
        } else {
            $response->setError(404, 'not found ' . $type . ' : ' . $keyvalue);
        }

        return $response;
    }

    /**
     * Return a filepath from a single filename, only contained in the public databasedir.
     * Valid path :
     * calendar/1.json , new/foobar.json, index/index.json , ...
     * Invalid path :
     * /var/www/private/somefile.sh.
     *
     * $filename : calendar/1.json , new/foobar.json, index/index.json , ...
     *
     * @param string $filename file
     *
     * @return Response object
     */
    public function getFilePath(string $filename): Response
    {
        $response = $this->getDefaultResponse();

        //
        //forbid upper directory
        //
        if (strpos($filename, '..') !== false) {
            throw new \Exception('Invalid path ' . $filename, 1);
        }

        $file = $this->databasedir . '/' . $filename;

        // get one element
        if (file_exists($file)) {
            $response->setResult($file);
            $response->setCode(200);
        } else {
            $response->setError(404, 'not found ' . $file);
        }

        return $response;
    }

    /**
     * Return a single element, from a JSON array stored in file.
     * $filename : JSON data filename eg: [{"id":"1", "foo":"bar"}, {"id":"2", "foo":"bar2"}]
     * $keyname : primary key inside the file eg : id
     * $keyvalue : eg : 1.
     *
     * @param string $filename: index.json
     * @param string $keyname   : id
     * @param string $keyvalue  : 1
     *
     * @return : Response object with a JSON object eg : {"id":"1", "foo":"bar"}
     */
    public function get(string $filename, string $keyname, string $keyvalue): Response
    {
        $response = $this->getDefaultResponse();

        // Read the JSON file
        $file = $this->databasedir . '/' . $filename;
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
                $response->setError(404, 'not found ' . $keyname . ' : ' . $keyvalue);
            }
        } else {
            // return all
            $response->setResult(json_encode($data));
            $response->setCode(200);
        }

        return $response;
    }

    /**
     * Get all JSON files list of a directory
     * eg: [{"id":"1", "filename": "1.json"}, {"id":"2", "filename": "2.json"}].
     *
     * @param string $type eg: calendar
     *
     * @return : Response object with a JSON array
     */
    public function getAllObjects(string $type): Response
    {
        if (!isset($type)) {
            throw new \Exception('empty type');
        }
        $response = $this->getDefaultResponse();

        $thelist = [];

        if ($handle = opendir($this->databasedir . '/' . $type)) {
            while (false !== ($file = readdir($handle))) {
                $fileObject = json_decode('{}');
                if ($file != '.' && $file != '..' && strtolower(substr($file, strrpos($file, '.') + 1)) == 'json') {
                    $fileObject->{'filename'} = $file;
                    $fileObject->{'id'} = str_replace('.json', '', $file);
                    array_push($thelist, $fileObject);
                }
            }
            closedir($handle);
        }

        $response->setResult(json_encode($thelist));
        $response->setCode(200);

        return $response;
    }

    /**
     * Get all elements from an array, contained in a single file.
     *
     * @param string $filename : JSON data filename eg: [{"id":"1", "foo":"bar"}, {"id":"2", "foo":"bar2"}].
     *
     * @return : Response object with a JSON array
     */
    public function getAll(string $filename): Response
    {
        $response = $this->getDefaultResponse();

        // Read the JSON file
        $file = $this->databasedir . '/' . $filename;
        $data = JsonUtils::readJsonFile($file);
        if (isset($data)) {
            $response->setCode(200);
            $response->setResult(json_encode($data));
        }

        return $response;
    }

    /**
     * Create a single element.
     *
     * @param string $type      : object type (eg : calendar)
     * @param string $keyname   : JSON data filename
     * @param string $recordStr : primary key inside the file.
     *
     * @return : Response object with a JSON object
     */
    public function put(string $type, string $keyname, string $recordStr): Response
    {
        // TODO : throw an exception if existing file
        return $this->post($type, $keyname, $recordStr);
    }



    /**
     * Return an index file path.
     *
     * @param string $type : name of type eg : calendar
     *
     * @return string /foobar/calendar/index.json
     */
    public function getIndexFileName(string $type) : string
    {
        if (!isset($type)) {
            throw new \Exception('empty type', 1);
        }

        return $this->databasedir . '/' . $type . '/index/index.json';
    }


    /**
     * Save a record.
     *
     * @param string $type      : object type (eg : calendar)
     * @param string $keyname   : primary key inside the file.
     * @param string $recordStr : JSON data
     */
    public function post(string $type, string $keyname, string $recordStr)
    {
        $response = $this->getDefaultResponse();

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
        } else {
            $response->setError(400, 'Bad object parameters');
        }

        return $response;
    }

    /**
     * Update a record.
     *
     * @param string $type      : object type (eg : calendar)
     * @param string $keyname   : primary key inside the file.
     * @param string $recordStr : JSON data
     */
    public function update(string $type, string $keyname, string $recordStr): Response
    {
        $response = $this->getDefaultResponse();

        if (isset($recordStr)) {
            // Decode JSON
            $myobjectJson = json_decode($recordStr);
            $response->setResult($recordStr);
            unset($recordStr);

            // detect id
            $id = $myobjectJson->{$keyname};
            // file name
            $file = $this->getItemFileName($type, $id);

            $existing = JsonUtils::readJsonFile($file);
            JsonUtils::copy($myobjectJson, $existing);

            // write to file
            JsonUtils::writeJsonFile($file, $existing);
            unset($myobjectJson);
            $response->setCode(200);
        } else {
            $response->setError(400, 'Bad object parameters');
        }

        return $response;
    }

    /**
     * Add object id to index.
     *
     * @param string $type      : object type (eg : calendar)
     * @param string $keyname   : primary key inside the file.
     * @param string $recordStr : JSON data
     */
    public function publishById(string $type, string $keyname, string $keyvalue): Response
    {
        $response = $this->getDefaultResponse();

        // file name eg: index.json
        $file = $this->getIndexFileName($type);
        // create a backup of previous index file eg: history/index-TIMESTAMP.json
        if ($this->enableIndexHistory) {
            $backupDone = $this->mycopy($file, $this->getBackupIndexFileName($type));
        }
        /*
        Load a template for index.
        eg :
            { "id": "", "date": "",  "activity": "", "title": "" }
        */

        $indexValue = JsonUtils::readJsonFile($this->getIndexTemplateFileName($type));

        // Read the full JSON record
        $recordFile = $this->databasedir . '/' . $type . '/' . $keyvalue . '.json';

        $record = JsonUtils::readJsonFile($recordFile);

        //copy some fields to index
        JsonUtils::copy($record, $indexValue);
        unset($record);

        // get index data
        $data = JsonUtils::readJsonFile($file);
        $data = JsonUtils::put($data, $keyname, $indexValue);

        // write to file
        JsonUtils::writeJsonFile($file, $data);
        unset($data);

        $response->setCode(200);
        // set a timestamp response
        $tempResponse = json_decode($response->getResult());
        $tempResponse->{'timestamp'} = '' . time();
        $response->setResult(json_encode($tempResponse));

        return $response;
    }

    /**
     * Rebuild an index.
     *
     * @param string $type    : object type (eg : calendar)
     * @param string $keyname : primary key inside the file.
     */
    public function rebuildIndex(string $type, string $keyname): Response
    {
        $response = $this->getDefaultResponse();

        $data = [];

        // file name eg: index.json

        $indexFile = $this->getIndexFileName($type);

        /*
        Load a template for index.
        eg :
            { "id": "", "date": "",  "activity": "", "title": "" }
        */

        $indexTemplate = JsonUtils::readJsonFile($this->getIndexTemplateFileName($type));

        if ($handle = opendir($this->databasedir . '/' . $type)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..' && strtolower(substr($file, strrpos($file, '.') + 1)) == 'json') {
                    // Read the full JSON record
                    $record = JsonUtils::readJsonFile($this->databasedir . '/' . $type . '/' . $file);

                    //
                    //copy some fields to index
                    //
                    $indexValue = clone $indexTemplate;

                    JsonUtils::copy($record, $indexValue);
                    unset($record);
                    array_push($data, $indexValue);
                    unset($indexValue);
                }
            }
            closedir($handle);
        }

        //sort
        usort($data, compareIndex($keyname));

        // write to file

        JsonUtils::writeJsonFile($indexFile, $data);
        unset($data);
        $response->setCode(200);

        return $response;
    }



    /**
     * Options files content.
     *
     * @param string $filename file
     *
     * @return string options value
     */
    public function options(string $filename): string
    {
        $file = $this->databasedir . '/' . $filename;

        return json_encode(JsonUtils::readJsonFile($file));
    }

    /**
     * Initialize a default Response object.
     *
     * @return Response object
     */
    protected function getDefaultResponse() : Response
    {
        $response = new Response();
        $response->setCode(400);
        $response->setResult('{}');

        return $response;
    }
}
