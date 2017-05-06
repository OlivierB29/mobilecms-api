<?php
require_once 'utils/SecureRestApi.php';
require_once 'utils/ContentService.php';
/*
 * /api/v1/content/cake?filter=foobar
 */
class CmsApi extends SecureRestApi {
	const INDEX_JSON = '/index/index.json';
	const REQUESTBODY = 'requestbody';
	const ID = 'id';
	const TYPE = 'type';
	public function __construct($conf) {
		parent::__construct ( $conf );
	}

	/**
	 * /api/v1/content
	 */
	protected function content() {
		$this->checkConfiguration ();

		$datatype = $this->getDataType ();
		$service = new ContentService ( $this->conf->{'publicdir'} );
		if (isset ( $datatype ) && strlen ( $datatype ) > 0) {
			if ($this->method === 'GET') {

				// $response = $service->getAll($datatype . CmsApi::INDEX_JSON);
				$response = $service->getAllObjects ( $datatype );

				return $response->getResult ();
			} elseif ($this->method === 'POST') {

				$response = $service->post ( $datatype, CmsApi::ID, $this->request ['requestbody'] );

				$debug = json_decode("{}");
				$debug->{'msg'} = $response->getMessage();
				return json_encode($debug);

			}
		} else {
			if ($this->method === 'GET') {
				return $service->options ( 'types.json' );
			}
		}
	}
	private function getDataType(): string {
		$datatype = null;
		if (isset ( $this->verb )) {
			$datatype = $this->verb;
		}
		if (! isset ( $datatype )) {
			throw new Exception ( 'Empty datatype' );
		}
		return $datatype;
	}
	private function checkConfiguration() {
		if (! isset ( $this->conf->{'publicdir'} )) {
			throw new Exception ( 'Empty publicdir' );
		}
	}

	/**
	* http://stackoverflow.com/questions/25727306/request-header-field-access-control-allow-headers-is-not-allowed-by-access-contr
	*/
	public function options() {
		header ( "Access-Control-Allow-Methods: *" );
		header ( "Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With" );
	}

}
