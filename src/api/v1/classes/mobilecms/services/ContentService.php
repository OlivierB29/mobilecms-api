<?php namespace mobilecms\services;

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
        $result = 0;
        if (!empty($a) && !empty($b) && !empty($a->{$key}) && !empty($b->{$key})) {
            $result = strnatcmp($a->{$key}, $b->{$key});
        }
        return $result;
    };
}

function compareIndexReverse(string $key)
{
    /*
    * compare two object using the $key property
    * @param a first object to compare
    * @param b second object to compare
    */
    return function (\stdClass $a, \stdClass $b) use ($key) {
        $result = 0;
        if (!empty($a) && !empty($b) && !empty($a->{$key}) && !empty($b->{$key})) {
            $result = strnatcmp($b->{$key}, $a->{$key});
        }
        return $result;
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
     * Main directory (eg: /opt/foobar/data ).
     */
    private $databasedir;

    /**
     * logger
     */
    private $logger;


    /**
     * Constructor.
     *
     * @param databasedir $databasedir eg : public
     */
    public function __construct(string $databasedir)
    {
        $this->databasedir = $databasedir;
        $this->logger = new \mobilecms\utils\Logger();
    }

    /**
     * Return a record file path.
     *
     * @param string $type : name of type eg : calendar
     * @param string $id   : unique id of record eg : 1
     *
     * @return /foobar/calendar/index.json
     */
    private function getItemFileName(string $type, string $id, \stdClass $record) : string
    {
        $result = $this->databasedir . '/' . $type . '/' ;
        // conf "organizeby": "year"
        $conf = $this->getConf($type);
        if (!empty($conf->getString('organizeby'))) {
            // get year from date field
            $recorddate = $record->{$conf->getString('organizefield')};
            $year = substr($recorddate, 0, 4);
            // date should be mandatory
            if (!empty($year)) {
                $result .=  $year . '/';
            }
                        
        } 
        $result .= $id . '.json';
        return $result; 
    }

    private function checkParams(string $type, string $id)
    {
        $this->checkType($type);


        if (empty($id)) {
            throw new \Exception('empty id');
        }
    }

    private function checkType(string $type)
    {
        if (empty($type)) {
            throw new \Exception('empty type');
        }
    }


    /**
     * Return a template index file path.
     *
     * @param string $type : name of type eg : calendar
     *
     * @return /foobar/calendar/index_template.json
     */
    private function getIndexTemplateFileName(string $type) : string
    {
        $this->checkType($type);

        return $this->databasedir . '/' . $type . '/index/index_template.json';
    }

    /**
     * Return a template index cache path.
     *
     * @param string $type : name of type eg : calendar
     *
     * @return /foobar/calendar/cache_template.json
     */
    private function getCacheTemplateFileName(string $type) : string
    {
        $this->checkType($type);

        return $this->databasedir . '/' . $type . '/index/cache_template.json';
    }

    private function getConfFileName(string $type) : string
    {
        $this->checkType($type);

        return $this->databasedir . '/' . $type . '/index/conf.json';
    }

    public function getMetadataFileName(string $type) : string
    {
        $this->checkType($type);

        return $this->databasedir . '/' . $type . '/index/metadata.json';
    }


    public function getTemplateFileName(string $type) : string
    {
        $this->checkType($type);

        return $this->databasedir . '/' . $type . '/index/new.json';
    }


    /**
     * Get a single record.
     *
     * @param string $type     eg: calendar
     * @param string $keyvalue : id value, eg :1
     */
    public function getRecord(string $type, string $keyvalue) : \mobilecms\rest\Response
    {
        $response = $this->getDefaultResponse();

        // Read the JSON file
        $file = $this->getItemFileName($type, $keyvalue);

        // get one element
        if (file_exists($file)) {
            $response->setResult(\mobilecms\utils\JsonUtils::readJsonFile($file));
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
        $file = $this->getItemFileName($type, $keyvalue);

        if (file_exists($file)) {
            unlink($file);

            $response->setCode(200);
        } else {
            // @codeCoverageIgnoreStart
            $response->setError(404, 'not found ' . $type . ' : ' . $keyvalue);
            // @codeCoverageIgnoreEnd
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
    public function getFilePath(string $filename): \mobilecms\rest\Response
    {
        $response = $this->getDefaultResponse();

        //
        //forbid upper directory
        //
        if (strpos($filename, '..') !== false) {
            // @codeCoverageIgnoreStart
            throw new \Exception('Invalid path ' . $filename);
            // @codeCoverageIgnoreEnd
        }

        $file = $this->databasedir . '/' . $filename;

        // get one element
        if (file_exists($file)) {
            $response->setResult($file);
            $response->setCode(200);
        } else {
            // @codeCoverageIgnoreStart
            $response->setError(404, 'not found ' . $file);
            // @codeCoverageIgnoreEnd
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
     * @return : \mobilecms\rest\Response object with a JSON object eg : {"id":"1", "foo":"bar"}
     */
    public function get(string $filename, string $keyname, string $keyvalue): \mobilecms\rest\Response
    {
        $response = $this->getDefaultResponse();

        // Read the JSON file
        $file = $this->databasedir . '/' . $filename;
        $data = \mobilecms\utils\JsonUtils::readJsonFile($file);

        // get one element
        if (isset($keyvalue)) {
            // extract element data
            $existingObject = \mobilecms\utils\JsonUtils::getByKey($data, $keyname, $keyvalue);
            if (isset($existingObject)) {
                $response->setResult($existingObject);
                $response->setCode(200);
            } else {
                // element not found
                $response->setError(404, 'not found ' . $keyname . ' : ' . $keyvalue);
            }
        } else {
            // return all
            $response->setResult($data);
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
     * @return : \mobilecms\rest\Response object with a JSON array
     */
    public function getAllObjects(string $type): \mobilecms\rest\Response
    {
        $this->checkType($type);
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

        $response->setResult($thelist);
        $response->setCode(200);

        return $response;
    }

    /**
     * Get all elements from an array, contained in a single file.
     *
     * @param string $filename : JSON data filename eg: [{"id":"1", "foo":"bar"}, {"id":"2", "foo":"bar2"}].
     *
     * @return : \mobilecms\rest\Response object with a JSON array
     */
    public function getAll(string $filename): \mobilecms\rest\Response
    {
        $response = $this->getDefaultResponse();

        // Read the JSON file
        $file = $this->databasedir . '/' . $filename;
        $data = \mobilecms\utils\JsonUtils::readJsonFile($file);
        if (isset($data)) {
            $response->setCode(200);
            $response->setResult($data);
        }

        return $response;
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
        $this->checkType($type);

        return $this->databasedir . '/' . $type . '/index/index.json';
    }


    /**
     * Save a record.
     *
     * @param string $type      : object type (eg : calendar)
     * @param string $keyname   : primary key inside the file.
     * @param string $recordStr : JSON data
     */
    public function post(string $type, string $keyname, \stdClass $record)
    {
        $this->checkParams($type, $keyname);
        $response = $this->getDefaultResponse();

        if (!empty($record) && !empty($record->{$keyname})) {
            $response->setResult($record);

            // detect id
            $id = $record->{$keyname};

            // file name
            $file = $this->getItemFileName($type, $id, $record);

            // write to file
            \mobilecms\utils\JsonUtils::writeJsonFile($file, $record);
            unset($record);
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
     * @param \stdClass $record : JSON data
     */
    public function update(string $type, string $keyname, \stdClass $record): \mobilecms\rest\Response
    {
        $response = $this->getDefaultResponse();

        if (!empty($record)) {
            $response->setResult($record);
            // detect id
            $id = $record->{$keyname};
            // file name
            $file = $this->getItemFileName($type, $id);

            $existing = \mobilecms\utils\JsonUtils::readJsonFile($file);
            \mobilecms\utils\JsonUtils::copy($record, $existing);

            // write to file
            \mobilecms\utils\JsonUtils::writeJsonFile($file, $existing);
            unset($record);
            $response->setCode(200);
        } else {
            // @codeCoverageIgnoreStart
            $response->setError(400, 'Bad object parameters');
            // @codeCoverageIgnoreEnd
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
    public function publishById(string $type, string $keyname, string $keyvalue): \mobilecms\rest\Response
    {
        $this->logger->info('publishById' . $type . ',' . $keyname . ',' . $keyvalue);
        
        $response = $this->getDefaultResponse();

        // file name eg: index.json
        $file = $this->getIndexFileName($type);
        /*
        Load a template for index.
        eg :
            { "id": "", "date": "",  "activity": "", "title": "" }
        */
        $indexValue = null;
        // create an indexed with cached items
        if (\file_exists($this->getCacheTemplateFileName($type))) {
            $indexValue = \mobilecms\utils\JsonUtils::readJsonFile($this->getCacheTemplateFileName($type));
        } else {
            $indexValue = \mobilecms\utils\JsonUtils::readJsonFile($this->getIndexTemplateFileName($type));
        }

        

        // Read the full JSON record
        $recordFile = $this->databasedir . '/' . $type . '/' . $keyvalue . '.json';

        $record = \mobilecms\utils\JsonUtils::readJsonFile($recordFile);

        //copy some fields to index
        \mobilecms\utils\JsonUtils::copy($record, $indexValue);
       

        // get index data
        $data = \mobilecms\utils\JsonUtils::readJsonFile($file);
        $data = \mobilecms\utils\JsonUtils::put($data, $keyname, $indexValue);

        // write to file
        \mobilecms\utils\JsonUtils::writeJsonFile($file, $data);
        unset($data);
        unset($record);



        $response->setCode(200);
        // set a timestamp response
        // $tempResponse = $response->getResult();
        // $tempResponse->{'timestamp'} = '' . time();
        // $response->setResult($tempResponse);

        return $response;
    }

    public function getConf(string $type): \mobilecms\utils\Properties
    {
        $conf = null;
        if (\file_exists($this->getConfFileName($type))) {
            $conf = new \mobilecms\utils\Properties();
            $conf->loadConf($this->getConfFileName($type));
        }
        
       return $conf;
    }

    /**
     * Rebuild an index.
     *
     * @param string $type    : object type (eg : calendar)
     * @param string $keyname : primary key inside the file.
     */
    public function rebuildIndex(string $type, string $keyname): \mobilecms\rest\Response
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

        $indexTemplate = \mobilecms\utils\JsonUtils::readJsonFile($this->getIndexTemplateFileName($type));

        $cacheTemplate = null;


        if ($handle = opendir($this->databasedir . '/' . $type)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..' && strtolower(substr($file, strrpos($file, '.') + 1)) == 'json') {
                    // Read the full JSON record
                    $filename = $this->databasedir . '/' . $type . '/' . $file;
                    $record = \mobilecms\utils\JsonUtils::readJsonFile($filename);

                    //
                    //copy some fields to index
                    //
                    $indexValue = clone $indexTemplate;

                    \mobilecms\utils\JsonUtils::copy($record, $indexValue);
                    unset($record);
                    array_push($data, $indexValue);
                    unset($indexValue);
                }
            }
            closedir($handle);
        }



        // sorted index by a field, or by id
        $sortby = $keyname;
        $sortAscending = false;
        $cacheSize = -1;
        if ($this->getConf() != null) {
            if (!empty($this->getConf()->getString('sortby'))) {
                $sortby = $this->getConf()->getString('sortby');
            }
            if ('asc' === $this->getConf()->getString('sortdirection')) {
                $sortAscending = true;
            }
            $cacheSize = $this->getConf()->getInteger('cachesize', 0);
        }

        // sort
        if ($sortAscending) {
            usort($data, compareIndex($sortby));
        } else {
            usort($data, compareIndexReverse($sortby));
        }

        // create an indexed with cached items
        if (\file_exists($this->getCacheTemplateFileName($type))) {
            $cacheTemplate = \mobilecms\utils\JsonUtils::readJsonFile($this->getCacheTemplateFileName($type));
            $i = 0;
            while ($i < $cacheSize && $i < count($data)) {
                $file = $data[$i]->{$keyname};
                $filename = $this->databasedir . '/' . $type . '/' . $file . '.json';
                $record = \mobilecms\utils\JsonUtils::readJsonFile($filename);
                $cacheValue = clone $cacheTemplate;
                \mobilecms\utils\JsonUtils::copy($record, $cacheValue);
                $data[$i] = $cacheValue;
                $i++;
            }
        }


        // write to file
        \mobilecms\utils\JsonUtils::writeJsonFile($indexFile, $data);
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
    public function options(string $filename)
    {
        $file = $this->databasedir . '/' . $filename;

        return \mobilecms\utils\JsonUtils::readJsonFile($file);
    }
    public function adminOptions(string $filename)
    {
        $file = $this->databasedir . '/' . $filename;
        //  $tmp = json_decode('{}');
        //  $tmp->{'list'} = \mobilecms\utils\JsonUtils::readJsonFile($file);
        $tmp = \mobilecms\utils\JsonUtils::readJsonFile($file);

        return $tmp;
    }

    /**
     * Initialize a default Response object.
     *
     * @return Response object
     */
    protected function getDefaultResponse() : \mobilecms\rest\Response
    {
        $response = new \mobilecms\rest\Response();
        $response->setCode(400);
        $response->setResult(new \stdClass);

        return $response;
    }


    public function deleteRecords(string $type, array $ids)
    {
        $response = $this->getDefaultResponse();

        foreach ($ids as $id) {

                // Read the JSON file
            $file = $this->databasedir . '/' . $type . '/' . $id . '.json';

            if (file_exists($file)) {
                unlink($file);

                $response->setCode(200);
            } else {
                // @codeCoverageIgnoreStart
                $response->setError(404, 'not found ' . $type . ' : ' . $id);
                // @codeCoverageIgnoreEnd
            }
        }



        return $response;
    }

    public function getLogger() : \mobilecms\utils\Logger
    {
        return $this->logger;
    }


    public function setLogger(\mobilecms\utils\Logger $logger)
    {
        $this->logger = $logger;
    }
}
