<?php
/*
 * Response object for services
 */
class Response
{
    /**
     * result.data.
     */
    private $result;

    /**
     * http return code to return.
     */
    private $code;

    /**
    * constructor
    */
    public function __construct()
    {
        $this->result = '{}';
    }

    /**
    * set string result
    * @param newval set string result
    */
    public function setResult(string $newval)
    {
        $this->result = $newval;
    }

    /**
    * @@return get string result
    */
    public function getResult(): string
    {
        return $this->result;
    }

    /**
    * @param newval set http status code
    */
    public function setCode(int $newval)
    {
        $this->code = $newval;
    }

    /**
    * @@return get get http status code
    */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
    * set an error message and format to JSON
    * @param code http status code
    * @param msg set error message
    */
    public function setError(int $code, string $msg)
    {
        $this->code = $code;

        $json = json_decode('{}');
        $json->{'error'} = $msg;
        $this->result = json_encode($json);
    }
}
