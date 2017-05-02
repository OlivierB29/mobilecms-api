<?php
/*
 * Response object for services
 */
class Response implements JsonSerializable {
	
	/**
	 * String data of the result
	 */
	private $result;
	
	/**
	 * optional message
	 */
	private $message;
	
	/**
	 * http return code to return
	 */
	private $code;
	public function jsonSerialize() {
		return array (
				'result' => $this->result,
				'message' => $this->message,
				'code' => $this->code 
		);
	}
	function __construct() {
		$this->result = "";
		$this->message = "";
	}
	public function setResult(string $newval) {
		$this->result = $newval;
	}
	public function getResult(): string {
		return $this->result;
	}
	public function setMessage(string $newval) {
		$this->message = $newval;
	}
	public function getMessage(): string {
		return $this->message;
	}
	public function setCode(int $newval) {
		$this->code = $newval;
	}
	public function getCode(): int {
		return $this->code;
	}
	public function appendMessage(string $newval) {
		$this->message .= $newval;
	}
}

?>
