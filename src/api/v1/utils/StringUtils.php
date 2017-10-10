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
     * @param question
     */
    public static function isNotEmpty(string $question)
    {
        return isset($question) && trim($question) != EMPTYSTR;
    }

    /**
     * compare strings.
     *
     * @param a str a
     * @param b str b
     *
     * @return bool result
     */
    public static function eq(string $a, string $b)
    {
        return strcmp($a, $b) == 0;
    }

    /**
     * starts with string ?
     *
     * @param haystack eg "foobar"
     * @param needle eg "foo"
     *
     * @return bool result
     */
    public static function startsWith(string $haystack, string $needle)
    {
        $length = strlen($needle);

        return substr($haystack, 0, $length) === $needle;
    }

    /**
     * ends with string ?
     *
     * @param haystack eg "foobar"
     * @param needle eg "bar"
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
