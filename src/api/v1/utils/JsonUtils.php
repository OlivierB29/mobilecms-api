<?php

include_once 'StringUtils.php';

/**
 * JSON utility for PHP.
 */
class JsonUtils
{
    public function __construct()
    {
    }

    /**
    * @param $file : file
     * @return JSON object (array or stdClass)
     */
    public static function readJsonFile(string $file)
    {
        return json_decode(file_get_contents($file));
    }

    /*
    *  write JSON object (array or stdClass)
    * @param $file : file
    * @param $data : JSON object
   */
    public static function writeJsonFile(string $file, $data)
    {
        $fh = fopen($file, 'w') or die('Error opening output file' . $file);
        fwrite($fh, json_encode($data, JSON_PRETTY_PRINT));
        fclose($fh);
    }

    /**
     * find a JSON object into a JSON array, by key=value.
     * @param $data : Array
     * @param $name : eg: id
     * @param $value : eg: 123
     */
    public static function getByKey(array $data, string $name, string $value)
    {
        $result = null;

        if (isset($name) && isset($value)) {
            foreach ($data as $element) {
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
     * @param $data : Array
     * @param $name : eg: id
     * @param $item : JSON object
     * @return updated array
     */
    public static function put(array $data, string $name, stdClass $item): array
    {
        $existing = self::getByKey($data, $name, $item->{$name});

        if (isset($existing)) {
            self::replace($item, $existing);
        } else {
            array_push($data, $item);
        }

        return $data;
    }

    /**
     * copy properties of $source to $dest, without including the new properties
     * convert to --> $dest = {"id":"1", "foo":"pub"}.
     * @param $source = {"id":"1", "foo":"pub" , "hello":"world"}
     * @param $dest = {"id":"1", "foo":"bar"}
     */
    public static function copy(stdClass $source, stdClass $dest)
    {
        foreach ($dest as $key => $value) {
            if (isset($source->{$key})) {
                $dest->{$key} = $source->{$key};
            }
        }
    }

    /**
     * copy properties of $source to $dest, including the new properties
     * eg:--> $dest = {"id":"1", "foo":"pub" , "hello":"world"}.
     * @param $source = {"id":"1", "foo":"pub" , "hello":"world"}
     * @param $dest = {"id":"1", "foo":"bar"}
     */
    public static function replace(stdClass $source, stdClass $dest)
    {
        foreach ($source as $key => $value) {
            $dest->{$key} = $source->{$key};
        }
    }
}
