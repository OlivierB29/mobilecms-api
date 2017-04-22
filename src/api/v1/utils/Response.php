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
	public function setResult($newval) {
		$this->result = $newval;
	}
	public function getResult() {
		return $this->result;
	}
	public function setMessage($newval) {
		$this->message = $newval;
	}
	public function getMessage() : string {
		return $this->message;
	}
	public function setName($newval) {
		$this->name = $newval;
	}
	public function getName() : string {
		return $this->name;
	}
	public function setCode($newval) {
		$this->code = $newval;
	}
	public function getCode() {
		return $this->code;
	}
	public function appendMessage($newval) {
		$this->message .= $newval;
	}
}

?>
