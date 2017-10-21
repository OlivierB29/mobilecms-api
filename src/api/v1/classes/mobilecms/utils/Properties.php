<?php namespace mobilecms\utils;

class Properties
{
    private $conf;


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

    public function getString(string $key) : bool
    {
        $result = '';

        if (!empty($this->getConf()->{$key})) {
            $result = $this->getConf()->{$key};
        }
        return $result;
    }

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
