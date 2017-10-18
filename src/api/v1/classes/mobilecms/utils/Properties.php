<?php namespace mobilecms\utils;

class Properties
{
    private $conf;


    public getBoolean(string $key, bool $default) : bool {

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
    public function getConf()
    {
        return $this->conf;
    }
}
