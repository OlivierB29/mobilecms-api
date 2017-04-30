<?php
/*
 * Inspired by http://fr.wikihow.com/cr%C3%A9er-un-script-de-connexion-s%C3%A9curis%C3%A9e-avec-PHP-et-MySQL
 * This fork uses JSON as storage data
 */
include_once 'JsonUtils.php';
include_once 'JwtToken.php';
include_once 'Response.php';
/*
 * User management Utility.
 * Each user is stored in a separate JSON file.
 *
 * users/
 * -----/user1.json
 * -----/user2.json
 */
class UserService {

	/**
	 */
	private $databasedir;

	/*
	 * Available hash : hash_algos()
	 */
	private $algorithm = 'sha512';

	private $saltlength = 128;

	private $passwordHashCost = 12;

	function __construct($databasedir) {
		$this->databasedir = $databasedir;
	}

	/**
	 * return the users directory
	 */
	public function getJsonUserFile($email) {
		if (empty ( $this->databasedir )) {
			throw new Exception ( 'getJsonUserFile : empty conf' );
		}

		if (! empty ( $email )) {
			return $this->databasedir . '/' . strtolower ( $email ) . '.json';
		} else {
			throw new Exception ( 'getJsonUserFile : empty email' );
		}
	}

	/*
	 * return a JSON object of a user, null if not found
	 */
	public function getJsonUser($email) {
		$result = null;


		// file exists ?
		$file = $this->getJsonUserFile ( $email );
		if (file_exists ( $file )) {
			$jsonUser = JsonUtils::readJsonFile ( $file );

			if (isset ( $jsonUser->{'name'} ) && isset ( $jsonUser->{'password'} )) {
				$result = $jsonUser;
			} else {
				throw new Exception ( 'getJsonUser : empty user ' . $email);
			}
		}  else {
			throw new Exception ( 'getJsonUser : file not found' . $email);
		}

		return $result;
	}

	/**
	 * update a user
	 */
	public function updateUser($email, $name, $password, $salt, $role) {

		$result = false;

		$jsonUser = $this->getJsonUser ( $email );

		if (! empty ( $jsonUser )) {

			if ($name != '') {
				$jsonUser->{'name'} = $name;
			}

			if ($password != '') {
				$jsonUser->{'password'} = $password;
			}

			if ($salt != '') {
				$jsonUser->{'salt'} = $salt;
			}

			if ($role != '') {
				$jsonUser->{'role'} = $role;
			}

			// Modification
			$file = $this->getJsonUserFile ( $email );
			JsonUtils::writeJsonFile ( $file, $jsonUser );
			$result = true;
		} else {
			throw new Exception ( 'empty user' );
		}

		return $result;
	}

	/**
	 * create a new user file
	 */
	private function addDbUserWithSecret( $email, $name, $password, $salt, $role, $secretQuestion, $secretResponse) {
		if (! empty ( $email )) {
			$jsonUser = json_decode ( '{ "name" : "",  "email" : "",  "password" : "",  "salt" : "",  "secretQuestion" : "",  "secretResponse" : "",  "role" : ""}' );
			$jsonUser->{'name'} = $name;
			$jsonUser->{'email'} = $email;
			$jsonUser->{'password'} = $password;
			$jsonUser->{'salt'} = $salt;
			$jsonUser->{'role'} = $role;
			$jsonUser->{'secretQuestion'} = $secretQuestion;
			$jsonUser->{'secretResponse'} = $secretResponse;

			$file = $this->getJsonUserFile ( $email );
			JsonUtils::writeJsonFile ( $file, $jsonUser );
		} else {
			throw new Exception ( 'addDbUserWithSecret : empty email' );
		}
	}

	/**
	 * create a new user
	 */
	public function createUserWithSecret($emailParam, $username, $password, $secretQuestion, $secretResponse) {

		$email = strtolower ( $emailParam );

		$error_msg = null;

		if (empty ( $username )) {
			$error_msg .= 'InvalidUser';
		}

		if (! filter_var ( $email, FILTER_VALIDATE_EMAIL )) {
			// invalid email
			$error_msg .= 'InvalidEmail';
		}

		if (empty ( $password )) {
			$error_msg .= 'EmptyPassword';
		}

		if (empty ( $secretQuestion )) {
			$error_msg .= 'EmptySecretQuestion';
		}

		if (empty ( $secretResponse )) {
			$error_msg .= 'EmptySecretResponse';
		}

		// Cf forms.js
		// Ensure password length
		if (strlen ( $password ) > 128) {
			$error_msg .= 'InvalidPassword';
		}

		$file = $this->getJsonUserFile ( $email );


		if (file_exists ( $file )) {
			// user already exists
			$error_msg .= 'AlreadyExists.';
		}

		if (empty ( $error_msg )) {
			// create a random for this user
			// http://php.net/manual/fr/function.random-bytes.php
			$random_salt = base64_encode(  random_bytes( $this->saltlength  ) );


			//http://stackoverflow.com/questions/8952807/openssl-digest-vs-hash-vs-hash-hmac-difference-between-salt-hmac
			// store a salted password

			$options = [
	    'cost' => $this->passwordHashCost,
	    'salt' => $random_salt,
			];
			$saltpassword = password_hash($password, PASSWORD_BCRYPT, $options);

			// store a salted response
			$saltresponse = $this->hash( $this->algorithm, $this->concat($random_salt, $secretResponse));

			// create user
			$this->addDbUserWithSecret (  $email, $username, $saltpassword, $random_salt, 'guest', $secretQuestion, $saltresponse );
		}

		return $error_msg;
	}



	/*
	* Hash a string, such as a password
	*/
	private function hash($algorithm, $str) {
		return hash( $this->algorithm, $str );
	}

	/**
	* Concat a secret salt and a password.
	*/
	private function concat($random_salt, $password) {
		return $password . '.' . $random_salt;
	}


	/**
	 * authenticate
	 * return an empty string if success
	 */
	public function login($emailParam, $password) {
		$loginmsg = '';

		$debug = false;
		$debugmsg = 'debugmsg ';

		// if someone forgot to do this before
		$email = strtolower ( $emailParam );

		// return the existing user
		$user = $this->getJsonUser ( $email );

		// user found
		if (! empty ( $user )) {

			$db_password = $user->{'password'};

			if (password_verify($password, $db_password)) {
				// success
				$loginmsg = '';
			} else {
				// incorrect password
				$loginmsg = 'wrong passsword';
			}
		} else {
			// wrong user
			$loginmsg = 'wrong user';
			$debugmsg .= $email;
		}

		// return an empty string on success, so if debug is enabled, it's impossible to connect
		if ($debug) {
			$loginmsg .= $debugmsg;
		}
		return $loginmsg;
	}


	/**
	 * authenticate
	 * return an empty string if success
	 */
	public function getToken($emailParam, $password) : Response {
		//initialize Response
		$response = new Response ();
		$response->setCode ( 401 );
		$response->setMessage ( "Wrong login" );
		$response->setResult ( "{}" );

			$loginmsg = '';

			$debug = false;
			$debugmsg = 'debugmsg ';

			// if someone forgot to do this before
			$email = strtolower ( $emailParam );

			// return the existing user
			$user = $this->getJsonUser ( $email );

			// user found
			if (! empty ( $user )) {

				if (password_verify($password, $user->{'password'})) {
					$jwt = new JwtToken();

					$token = $jwt->createTokenFromUser($user->{'name'}, $user->{'email'}, $user->{'role'}, $user->{'salt'});
					if(isset($token)) {
							$response->setCode(200);
							$response->setResult($token);
							// success
							$loginmsg = '';
					}


				} else {
					// incorrect password
					$loginmsg = 'wrong passsword';
				}
			} else {
				// wrong user
				$loginmsg = 'wrong user';
				$debugmsg .= $email;
			}

			// return an empty string on success, so if debug is enabled, it's impossible to connect
			if ($debug) {
				$loginmsg .= $debugmsg;
			}
			return $response;

	//	http_response_code ( $response->getCode () );
	//	echo json_encode ( $response->getResult () );
	}

	public function verifyToken($token) : Response {
		$response = new Response ();
		$response->setCode ( 401 );
		$response->setMessage ( "Wrong token" );
		$response->setResult ( "{}" );

		try {
		$jwt = new JwtToken();

		//get payload and convert to JSON
		$payloadJson = json_decode($jwt->getPayload($token));

		// get the existing user
		$user = $this->getJsonUser ( $payloadJson->{'sub'} );


		//verify token with secret
		if($jwt->verifyToken($token, $user->{'salt'})) {
				$response->setCode ( 200 );
		}

	} catch ( Exception $e ) {
		$response->setCode ( 500 );
		$response->setMessage ( $e->getMessage () );
	} finally {
	}

	return $response;
	}

	//
}



?>
