<?php
include_once 'StringUtils.php';

/**
 * JSON utility for PHP
 */
class JsonUtils {
	public function __construct() {
	}
	
	/**
	 *
	 * @return JSON object (array or stdClass)
	 */
	public static function readJsonFile(string $file) {
		return json_decode ( file_get_contents ( $file ) );
	}
	
	/*
	 * write JSON object (array or stdClass)
	 */
	public static function writeJsonFile(string $file, $data) {
		$fh = fopen ( $file, 'w' ) or die ( "Error opening output file" . $file );
		fwrite ( $fh, json_encode ( $data, JSON_PRETTY_PRINT ) );
		fclose ( $fh );
	}
	
	/**
	 * find a JSON object into a JSON array, by key=value
	 */
	public static function getByKey(array $data, string $name, string $value) {
		$result = null;
		
		if (isset ( $name ) && isset ( $value )) {
			foreach ( $data as $element ) {
				if ($element->{$name} == $value) {
					$result = $element;
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * If the JSON array previously contained a mapping for the key,
	 * the old value is replaced by the specified value.
	 * 
	 * @return updated array
	 */
	public static function put(array $data, string $name, stdClass $item): array {
		$existing = JsonUtils::getByKey ( $data, $name, $item->{$name} );
		
		if (isset ( $existing )) {
			JsonUtils::replace ( $item, $existing );
		} else {
			array_push ( $data, $item );
		}
		
		return $data;
	}
	
	/**
	 * copy properties of $source to $dest, without including the new properties
	 * eg:
	 * $source = {"id":"1", "foo":"pub" , "hello":"world"}
	 * $dest = {"id":"1", "foo":"bar"}
	 * --> $dest = {"id":"1", "foo":"pub"}
	 */
	public static function copy(stdClass $source, stdClass $dest) {
		foreach ( $dest as $key => $value ) {
			$dest->{$key} = $source->{$key};
		}
	}
	
	/**
	 * copy properties of $source to $dest, including the new properties
	 * eg:
	 * $source = {"id":"1", "foo":"pub" , "hello":"world"}
	 * $dest = {"id":"1", "foo":"bar"}
	 * --> $dest = {"id":"1", "foo":"pub" , "hello":"world"}
	 */
	public static function replace(stdClass $source, stdClass $dest) {
		foreach ( $source as $key => $value ) {
			$dest->{$key} = $source->{$key};
		}
	}
}
