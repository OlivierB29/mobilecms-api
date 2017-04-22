<?php
include_once 'StringUtils.php';

/**
 * Utilitaire JSON en PHP
 */
class JsonUtils {
	public function __construct() {
	}
	public static function filterArray($data, $name, $value) {
		$result = array ();

		if (isset ( $name ) && isset ( $value )) {
			foreach ( $data as $element ) {
				if ($element->{$name} == $value) {
					array_push ( $result, $element );
				}
			}
		} else {
			$result = $data;
		}

		return $result;
	}
	public static function filterStartsWith($data, $name, $value) {
		$result = array ();

		if (isset ( $name ) && isset ( $value )) {
			foreach ( $data as $element ) {
				if (StringUtils::startsWith ( $element->{$name}, $value )) {
					array_push ( $result, $element );
				}
			}
		} else {
			$result = $data;
		}

		return $result;
	}

	/*
	 * TODO
	 */
	public static function filterArrayByJson($data, $object) {
		foreach ( $object as $name => $value ) {

			if (isset ( $name ) && isset ( $value )) {
				$data = JsonUtils::filterArray ( $data, $name, $value );
			}
		}

		return $data;
	}
	public static function readJsonFile($file) {

		
		$str_data = file_get_contents ( $file );
		$data = json_decode ( $str_data );
		return $data;
	}
	public static function writeJsonFile($file, $data) {
		$fh = fopen ( $file, 'w' ) or die ( "Error opening output file" );
		fwrite ( $fh, json_encode ( $data, JSON_PRETTY_PRINT ) );
		fclose ( $fh );
	}
	public static function updateObject($data, $object) {
		if (isset ( $data ) && isset ( $object )) {
			foreach ( $data as $element ) {
				// getByKey
				if ($element->{$keyname} == $object->{$keyname}) {
					JsonUtils::copy ( $object, $element );
				}

				array_push ( $result, $element );
			}

			/*
			 * TODO
			 * $element = JsonUtils::getByKey($data, $keyname, $keyvalue);
			 * JsonUtils::copy($object, $element);
			 */
		} else {
			$result = $data;
		}

		return $result;
	}

	/*
	 * TODO
	 */
	public static function updateByKey($data, $object, $keyname, $keyvalue) {
		$result = array ();

		if (isset ( $keyname ) && isset ( $keyvalue ) && isset ( $object )) {
			foreach ( $data as $element ) {
				// getByKey
				if ($element->{$keyname} == $object->{$keyname}) {
					JsonUtils::copy ( $object, $element );
				}

				array_push ( $result, $element );
			}

			/*
			 * TODO
			 * $element = JsonUtils::getByKey($data, $keyname, $keyvalue);
			 * JsonUtils::copy($object, $element);
			 */
		} else {
			$result = $data;
		}

		return $result;
	}
	public static function updateByIndex($data, $object, $index) {
		$result = array ();

		if (isset ( $data ) && isset ( $object ) && isset ( $index )) {
			$count = 0;
			foreach ( $data as $element ) {
				// getByIndex ?
				if ($count == $index) {
					JsonUtils::copy ( $object, $element );
				}

				array_push ( $result, $element );

				$count ++;
			}

			/*
			 * TODO
			 * $element = JsonUtils::getByKey($data, $keyname, $keyvalue);
			 * JsonUtils::copy($object, $element);
			 */
		} else {
			$result = $data;
		}

		return $result;
	}
	public static function getByKey($data, $name, $value) {
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
	public static function getByIndex($data, $index) {
		$result = null;
		$count = 0;

		if (isset ( $data ) && isset ( $index )) {
			foreach ( $data as $element ) {
				if ($index == $count) {
					$result = $element;
				}

				$count ++;
			}
		}

		return $result;
	}
	public static function copy($source, $dest) {
		foreach ( $source as $prop => $val ) {

			$dest->{$prop} = $source->{$prop};
		}
	}
	public static function setProperty($data, $name, $value) {
		foreach ( $data as $prop => $val ) {

			if ($prop == $name) {

				$dest->{$prop} = $value;
			}
		}
	}
	public static function sortByDateAsc($data) {
		usort ( $data, function ($a, $b) { // Sort the array using a user defined function
			return JsonUtils::intValue ( $b->{'date'} ) > JsonUtils::intValue ( $a->{'date'} ) ? - 1 : 1; // Compare the scores
		} );

		return $data;
	}
	public static function sortByDateDesc($data) {
		usort ( $data, function ($a, $b) { // Sort the array using a user defined function
			return JsonUtils::intValue ( $a->{'date'} ) > JsonUtils::intValue ( $b->{'date'} ) ? - 1 : 1; // Compare the scores
		} );

		return $data;
	}
	public static function getFeedByDate($data, $maxElements) {
		usort ( $data, function ($a, $b) { // Sort the array using a user defined function
			return JsonUtils::intValue ( $b->{'date'} ) > JsonUtils::intValue ( $a->{'date'} ) ? - 1 : 1; // Compare the scores
		} );

		// sample : 20161031
		$today = JsonUtils::intValue ( date ( '%Y%m%d' ) );

		// filter a max number of elements
		$result = array ();

		$i = 0;
		while ( $i ++ < count ( $data ) && count ( $result ) <= $maxElements ) {

			if (isset ( $data [$i]->{'date'} ) && JsonUtils::intValue ( $data [$i]->{'date'} ) >= $today) {
				array_push ( $result, $data [$i] );
			}
		}

		return $result;
	}
	public static function printDebug($element) {
		$result = '';

		foreach ( $element as $prop => $val ) {
			$result .= $prop . ' : ' . $val;
			$result .= '<br/>';
		}

		return $result;
	}

	/**
	 *
	 * @param string $date
	 *        	(YYYY/MM/DD YYYY-MM ... )
	 * @return string
	 */
	public static function intValue($date) {
		$result = 0;

		if (! empty ( $date )) {

			$str = preg_replace ( "/[^0-9]/", "", $date );

			// 201509 --> 20150900
			while ( strlen ( $str ) < 8 ) {
				$str .= '0';
			}

			if (! empty ( $str )) {

				$result = intval ( $str );
			}
		}

		return $result;
	}
}

?>
