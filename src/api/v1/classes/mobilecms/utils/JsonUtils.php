<?php namespace mobilecms\utils;

/**
 * JSON utility for PHP.
 */
class JsonUtils
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Read a JSON file.
     *
     * @param string $file : file path
     *
     * @return array or \stdClass JSON object
     */
    public static function readJsonFile(string $file)
    {
        return json_decode(file_get_contents($file));
    }

    /**
     * Read a JSON file.
     *
     * @param string $file : file
     * @param data   $data : JSON object
     */
    public static function writeJsonFile(string $file, $data)
    {
        $fh = null;

        try {
            if (file_exists($file) && !is_writable($file)) {
                throw new \Exception('Error opening output file' . $file);
            }
            $fh = fopen($file, 'w');
            if (!$fh) {
                die('Error opening output file' . $file);
            }

            fwrite($fh, json_encode($data, JSON_PRETTY_PRINT));
            fclose($fh);
        } catch (\Exception $e) {
            if (isset($fh)) {
                unset($fh);
            }

            throw $e;
        }
    }

    /**
     * Find a JSON object into a JSON array, by key=value.
     *
     * @param array  $data  : Array
     * @param string $name  : eg: id
     * @param string $value : eg: 123
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
     *
     * @param array $data : JSON Array
     * @param string $name : eg: id
     * @param \stdClass $item : JSON object
     *
     * @return updated array
     */
    public static function put(array $data, string $name, \stdClass $item): array
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
     * Copy properties of $source to $dest, without including the new properties
     * convert to --> $dest = {"id":"1", "foo":"pub"}.
     *
     * @param \stdClass $source = {"id":"1", "foo":"pub" , "hello":"world"}
     * @param \stdClass $dest = {"id":"1", "foo":"bar"}
     */
    public static function copy(\stdClass $source, \stdClass $dest)
    {
        foreach ($dest as $key => $value) {
            if (isset($source->{$key})) {
                $dest->{$key} = $source->{$key};
            }
        }
    }

    /**
     * Copy properties of $source to $dest, including the new properties
     * eg:--> $dest = {"id":"1", "foo":"pub" , "hello":"world"}.
     *
     * @param \stdClass $source = {"id":"1", "foo":"pub" , "hello":"world"}
     * @param \stdClass $dest = {"id":"1", "foo":"bar"}
     */
    public static function replace(\stdClass $source, \stdClass $dest)
    {
        foreach ($source as $key => $value) {
            $dest->{$key} = $source->{$key};
        }
    }
}
