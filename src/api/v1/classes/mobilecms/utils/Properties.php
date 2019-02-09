<?php namespace mobilecms\utils;

/**
* Read a simple JSON configuration file.
*/
class Properties
{
    /**
    * \stdClass JSON conf
    */
    private $conf;

    /**
     * Read an integer property.
     *
     * @param string $key : key
     * @param int $default : default value if configuration is empty
     * @param int value
     */
    public function getInteger(string $key, int $default = 0) : int
    {
        $result = $default;

        if (!empty($this->getConf()->{$key})) {
            if (\is_string($this->getConf()->{$key})) {
                $result = (int)$this->getConf()->{$key};
            } else {
                $result = $this->getConf()->{$key};
            }
        }
        return $result;
    }

    /**
     * Read a boolean property.
     *
     * @param string $key : key
     * @param bool $default : default value if configuration is empty
     * @param bool value
     */
    public function getBoolean(string $key, bool $default) : bool
    {
        $result = $default;

        if (!empty($this->getConf()->{$key})) {
            // if else with 'true' and 'false' string values :
            // it allow to use a default value
            if ('true' === $this->getConf()->{$key}) {
                $result = true;
            } elseif ('false' === $this->getConf()->{$key}) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Read a boolean property.
     *
     * @param string $key : key
     * @param string value
     */
    public function getString(string $key) : string
    {
        $result = '';

        if (!empty($this->getConf()->{$key})) {
            $result = $this->getConf()->{$key};
        }
        return $result;
    }

    /**
     * Read a JSON configuration file.
     *
     * @param string $file : file
     * @param \stdClass JSON conf
     */
    public function loadConf(string $file)
    {
        if (\file_exists($file)) {
            $this->setConf(json_decode(file_get_contents($file)));
        } else {
            throw new \Exception('Empty conf file');
        }
    }

    public function setConf(\stdClass $conf)
    {
        $this->conf = $conf;
    }

    /**
    * get JSON conf
    * @return \stdClass JSON conf
    */
    public function getConf(): \stdClass
    {
        return $this->conf;
    }
}
