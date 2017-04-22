<?php
define ( 'EMPTYSTR', '' );
class StringUtils {
	public static function isNotEmpty($question) {
		return isset ( $question ) && trim ( $question ) != EMPTYSTR;
	}
	public static function eq($a, $b) {
		return strcmp ( $a, $b ) == 0;
	}
	public static function startsWith($haystack, $needle) {
		$length = strlen ( $needle );
		return (substr ( $haystack, 0, $length ) === $needle);
	}
	public static function endsWith($haystack, $needle) {
		$length = strlen ( $needle );
		if ($length == 0) {
			return true;
		}
		
		return (substr ( $haystack, - $length ) === $needle);
	}
}

?>
