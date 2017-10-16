<?php namespace mobilecms\utils;

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
     * Constructor.
     */
    public function __construct()
    {
        $this->result = '{}';
    }

    /**
     * Set string result.
     *
     * @param string $newval set string result
     */
    public function setResult(string $newval)
    {
        $this->result = $newval;
    }

    /**
     * Get result.
     *
     * @return get string result
     */
    public function getResult(): string
    {
        return $this->result;
    }

    /**
     * Set http code.
     *
     * @param string $newval set http status code
     */
    public function setCode(int $newval)
    {
        $this->code = $newval;
    }

    /**
     * Get http code.
     *
     * @return get get http status code
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Set an error message and format it to JSON.
     *
     * @param string $code http status code
     * @param string $msg  set error message
     */
    public function setError(int $code, string $msg)
    {
        $this->code = $code;

        $json = json_decode('{}');
        $json->{'error'} = $msg;
        $this->result = json_encode($json);
    }
}
