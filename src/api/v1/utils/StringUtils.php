<?php

define('EMPTYSTR', '');
/**
 * Java-like StringUtils.
 */
class StringUtils
{
    /**
     * string is not empty.
     *
     * @param string $question
     */
    public static function isNotEmpty(string $question)
    {
        return isset($question) && trim($question) != EMPTYSTR;
    }

    /**
     * Compare strings.
     *
     * @param string $a str a
     * @param string $b str b
     *
     * @return bool result
     */
    public static function eq(string $a, string $b)
    {
        return strcmp($a, $b) == 0;
    }

    /**
     * Starts with string ?
     *
     * @param string $haystack eg "foobar"
     * @param string $needle eg "foo"
     *
     * @return bool result
     */
    public static function startsWith(string $haystack, string $needle)
    {
        $length = strlen($needle);

        return substr($haystack, 0, $length) === $needle;
    }

    /**
     * Ends with string ?
     *
     * @param string $haystack eg "foobar"
     * @param string $needle eg "bar"
     *
     * @return bool result
     */
    public static function endsWith(string $haystack, string $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return substr($haystack, -$length) === $needle;
    }
}
