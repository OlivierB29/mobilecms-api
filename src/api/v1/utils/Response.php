<?php
/*
 * Response object for services
 */
class Response
{
    /**
     * result.data
     */
    private $result;


    /**
     * http return code to return.
     */
    private $code;


    public function __construct()
    {
        $this->result = '{}';
    }

    public function setResult(string $newval)
    {
        $this->result = $newval;
    }

    public function getResult(): string
    {
        return $this->result;
    }


    public function setCode(int $newval)
    {
        $this->code = $newval;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function appendMessage(string $newval)
    {
        $this->message .= $newval;
    }

    public function setError(int $code, string $msg)
    {
        $this->code = $code;

        $json = json_decode('{}');
        $json->{'error'} = $msg;
        $this->result = json_encode($json);
    }


}
