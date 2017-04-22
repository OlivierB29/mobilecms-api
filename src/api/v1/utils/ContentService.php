<?php
require_once 'Response.php';
require_once 'JsonUtils.php';

/**
 * Read and save data from JSON files.
 * Future plans : consider http://stackoverflow.com/questions/13899342/can-we-use-json-as-a-database
 * Public methods :
 * - getAll : get all elements
 * - get : return a single element
 * - put : create a new item
 * - post : update an existing item
 */
class ContentService
{



	/**
	 * main directory (eg: /opt/foobar/data )
	 */
	private $databasedir;

	function __construct($databasedir) {
		$this->databasedir = $databasedir;
	}

    /**
    * return a single element
     * $filename : JSON data filename
     * $keyname : primary key inside the file
     * @return : Response object with a JSON object
     */
    public function get( string $filename, string $keyname, string $keyvalue)
    {
        $response = new Response();
        $response->setCode(400);
        $response->setMessage("Bad parameters");
        $response->setResult("{}");

        try {

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
                    $response->appendMessage('not found ' . $keyname . ' : ' . $keyvalue);
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
    * get all elements
     * $filename : JSON data filename
     * $keyname : primary key inside the file
     * @return : Response object with a JSON array
     */
    public function getAll( string $filename)
    {
        $response = new Response();
        $response->setCode(400);
        $response->setMessage("Bad parameters");
        $response->setResult('{}');

        try {

            // Read the JSON file
            $file = $this->databasedir . '/' . $filename;
            $data = JsonUtils::readJsonFile($file);
            if (isset($data)) {
                $response->setCode(200);
                $response->setResult(json_encode($data));
            }
        } catch (Exception $e) {
            $response->setCode(520);
            $response->setMessage($e->getMessage());
            $response->setResult('{ "result":"' . $e->getMessage() .'"}');
        } finally {
            return $response;
        }
    }

    /**
     * create a single element
     * $type : object type (eg : calendar)
     * $filename : JSON data filename
     * $keyname : primary key inside the file
     * @return : Response object with a JSON object
     */
    public function putById( string $type, string $keyname, string $recordStr)
    {
        //TODO : throw an exception if existing file
        return $this->postById($this->databasedir, $type, $keyname, $recordStr);
    }

		public function postItem( string $type, string $keyname, string $recordStr)
		{
			$response = new Response();
			$response->setCode(400);
			$response->setMessage("Bad parameters");
			$response->setResult("{}");

				try {
						if (isset($recordStr)) {

						//Decode JSON (and check if correct structure)
						$myobjectJson = json_decode($recordStr);

						//detect id
						$id = $myobjectJson->{$keyname};


						//create file name
						$file = $this->databasedir . '/' . $type . '/' . $id . '.json';
            JsonUtils::writeJsonFile($file, $myobjectJson);
						$response->setCode(200);

						} else {
								$response->setMessage("Bad parameters : id, object");
						}
				} catch (Exception $e) {
						$response->setCode(520);
						$response->setMessage($e->getMessage());
				} finally {
						return $response;
				}
		}

    /**
     * basic controls and save a single element
     * $this->databasedir : database Directory
     * $type
     * $filename : JSON data filename
     * $keyname : primary key inside the file
     * @return : Response object with a JSON object
     */
    public function postById( string $type, string $keyname, string $recordStr)
    {
        $response = new Response();
        $response->setCode(400);
        $response->setMessage("Bad parameters");
        $response->setResult("{}");

        try {
            if (isset($recordStr)) {
                $responseSave = $this->save($type, $keyname, $recordStr);

                if (isset($responseSave)) {
                    $response = $responseSave;
                }
            } else {
                $response->setMessage("Bad parameters : id, object");
            }
        } catch (Exception $e) {
            $response->setCode(520);
            $response->setMessage($e->getMessage());
        } finally {
            return $response;
        }
    }
    /**
     * TODO : save a single element, into a JSON array file. eg : [ {"id":"1", "foo":"bar"}, {"id":"2", "foo":"bar"} ]
     * $type : name of object (and subdirectory)
     * $filename : JSON data filename
     * $keyname : primary key inside the file
     * @return : Response object with a JSON object
     */
    private function save( string $type, string $keyname, string $recordStr)
    {
        $response = new Response();
        $response->setCode(400);
        //$response->setMessage('{"save" : "' . $this->databasedir . ' ' . $type . ' ' . $keyname . ' ' . $recordStr . '"}');
				$response->setMessage('');
        $response->setResult('{}');

        if (isset($this->databasedir) && isset($type) && isset($keyname)) {
            $response->setResult("isset OK");

            $myobjectJson = json_decode($recordStr);

            $id = $myobjectJson->{$keyname};

            $response->setResult("json_decode OK " . $id);

            // Read the JSON file
            $file = $this->databasedir . '/' . $type . '/' . $id . '.json';

            // $file = '/public/' . $type . '/' . $id . '.json' ;

            $response->setResult("file OK " . $file);
            $data = JsonUtils::readJsonFile($file);

            if (isset($data)) {

                // debug
                $response->appendMessage('debugging ...');

                JsonUtils::writeJsonFile($file, $recordStr);

                $response->appendMessage($file . " : saved, ");


                $response->setCode(200);
            } else {
                $response->appendMessage('not found ' . $id);
                $response->setCode(404);
            }
        } else {
            $response->appendMessage('empty params !');
        }

        return $response;
    }




}
