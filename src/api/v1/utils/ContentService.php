<?php
require_once 'Response.php';
require_once 'JsonUtils.php';

/**
 * Save an object and constructs a response
 */
class ContentService {

	/**
	 * $dir : database Directory
	 * $filename : JSON data filename
	 * $keyname : primary key inside the file
	 */
	public function get($dir, $filename, $keyname) {
		$response = new Response ();
		$response->setCode ( 400 );
		$response->setMessage ( "Bad parameters" );
		$response->setResult ( "{}" );

		try {

			// Read the JSON file
			$file = $dir . '/' . $filename;
			$data = JsonUtils::readJsonFile ( $file );

			// get one element
			if (isset ( $_GET ["id"] )) {
				$keyvalue = $_GET ["id"];

				// extract element data
				$existingObject = JsonUtils::getByKey ( $data, $keyname, $keyvalue );
				if (isset ( $existingObject )) {

					$response->setResult ( $existingObject );
					$response->setCode ( 200 );
				} else {
					// element not found
					$response->appendMessage ( 'not found ' . $keyname . ' : ' . $keyvalue );
					$response->setCode ( 404 );
				}
			} else {
				// return all
				$response->setResult ( $data );
				$response->setCode ( 200 );
			}
		} catch ( Exception $e ) {
			$response->setCode ( 520 );
			$response->setMessage ( $e->getMessage () );
		} finally {

			http_response_code ( $response->getCode () );
			echo json_encode ( $response->getResult () );
		}
	}
	public function getAll($dir, $filename) {
		$response = new Response ();
		$response->setCode ( 400 );
		$response->setMessage ( "Bad parameters" );
		$response->setResult ( '{}' );

		try {

			// Read the JSON file
			$file = $dir . '/' . $filename;
			$data = JsonUtils::readJsonFile ( $file );
			if (isset ( $data )) {
				$response->setCode ( 200 );
				$response->setResult ( $data );
			}
		} catch ( Exception $e ) {
			$response->setCode ( 520 );
			$response->setMessage ( $e->getMessage () );
			$response->setResult ( '{ "result":"' . $e->getMessage () .'"}');
		} finally {

			return $response;
		}
	}
	public function post($dir, $type, $keyname, $recordStr) {
		$response = new Response ();
		$response->setCode ( 400 );
		$response->setMessage ( "Bad parameters" );
		$response->setResult ( "{}" );

		try {

			if (isset ( $recordStr )) {

				$responseSave = $this->save ( $dir, $type, $keyname, $recordStr );

				if (isset ( $responseSave )) {
					$response = $responseSave;
				}
			} else {
				$response->setMessage ( "Bad parameters : id, object" );
			}
		} catch ( Exception $e ) {
			$response->setCode ( 520 );
			$response->setMessage ( $e->getMessage () );
		} finally {

		return $response;
		}
	}
	public function save($dir, $type, $keyname, $recordStr) {
		$response = new Response ();
		$response->setCode ( 400 );
		$response->setMessage ( "" );
		$response->setResult ( '{"save" : "' . $dir . ' ' . $type . ' ' . $keyname . ' ' . $recordStr . '"}' );

		if (isset ( $dir ) && isset ( $type ) && isset ( $keyname )) {
			$response->setResult ( "isset OK" );

			$myobjectJson = json_decode ( $recordStr );

			$id = $myobjectJson->{$keyname};

			$response->setResult ( "json_decode OK " . $id );

			// Read the JSON file
			$file = $dir . '/' . $type . '/' . $id . '.json';

			// $file = '/public/' . $type . '/' . $id . '.json' ;

			$response->setResult ( "file OK " . $file );
			$data = JsonUtils::readJsonFile ( $file );

			if (isset ( $data )) {

				// debug
				$response->appendMessage ( 'debugging ...' );

				JsonUtils::writeJsonFile ( $file, $recordStr );

				$response->appendMessage ( $file . " : saved, " );

				$response->setResult ( 'true' );
				$response->setCode ( 200 );
			} else {
				$response->appendMessage ( 'not found ' . $id );
				$response->setCode ( 404 );
			}
		} else {
			$response->appendMessage ( 'empty params !' );
		}

		return $response;
	}
}

?>
