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
class UserService
{
    /**
     * database directory.
     */
    private $databasedir;

    /*
     * Available hash : hash_algos()
     */
    private $algorithm = 'sha512';

    /**
     * default salt length.
     */
    private $saltlength = 128;

    /**
     * depends on server hardware.
     */
    private $passwordHashCost = 12;

    /**
     * Constructor.
     *
     * @param databasedir eg : public
     */
    public function __construct($databasedir)
    {
        $this->databasedir = $databasedir;
    }

    /**
     * Return the json user file eg : foobar@example.org.json.
     *
     * @param string $email : email
     *
     * @return string json user file eg : foobar@example.org.json
     */
    public function getJsonUserFile(string $email): string
    {
        if (empty($this->databasedir)) {
            throw new Exception('getJsonUserFile : empty conf');
        }

        if (!empty($email)) {
            return $this->databasedir . '/' . strtolower($email) . '.json';
        } else {
            throw new Exception('getJsonUserFile : empty email');
        }
    }

    /**
     * Return a JSON object of a user, null if not found.
     *
     * @param string $email : email
     *
     * @return stdClass user object
     */
    public function getJsonUser(string $email): stdClass
    {
        $result = null;

        // file exists ?
        $file = $this->getJsonUserFile($email);
        if (file_exists($file)) {
            $jsonUser = JsonUtils::readJsonFile($file);

            if (isset($jsonUser->{'name'}) && isset($jsonUser->{'password'})) {
                $result = $jsonUser;
            } else {
                throw new Exception('getJsonUser : empty user ' . $email);
            }
        } else {
            throw new Exception('getJsonUser : file not found ' . $file);
        }

        return $result;
    }

    /**
     * Update a user.
     *
     * @param string $email    : email
     * @param string $name     : name
     * @param string $password : password
     * @param string $salt     : private salt
     * @param string $role     : role none|editor|admin
     */
    public function updateUser(string $email, string $name, string $password, string $salt, string $role)
    {
        $result = false;

        $jsonUser = $this->getJsonUser($email);

        if (!empty($jsonUser)) {
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
            $file = $this->getJsonUserFile($email);
            JsonUtils::writeJsonFile($file, $jsonUser);
            $result = true;
        } else {
            throw new Exception('empty user');
        }

        return $result;
    }

    /**
     * Create a new user file.
     *
     * @param string $email          : email
     * @param string $name           : name
     * @param string $password       : password
     * @param string $salt           : private salt
     * @param string $role           : role none|editor|admin
     * @param string $secretQuestion : secret question (encoded)
     * @param string $secretResponse : secret question (encrypted)
     */
    private function addDbUserWithSecret(string $email, string $name, string $password, string $salt, string $role, string $secretQuestion, string $secretResponse)
    {
        if (!empty($email)) {
            $jsonUser = json_decode('{ "name" : "",  "email" : "",  "password" : "",  "salt" : "",  "secretQuestion" : "",  "secretResponse" : "",  "role" : ""}');
            $jsonUser->{'name'} = $name;
            $jsonUser->{'email'} = $email;
            $jsonUser->{'password'} = $password;
            $jsonUser->{'salt'} = $salt;
            $jsonUser->{'role'} = $role;
            $jsonUser->{'secretQuestion'} = $secretQuestion;
            $jsonUser->{'secretResponse'} = $secretResponse;

            $file = $this->getJsonUserFile($email);
            JsonUtils::writeJsonFile($file, $jsonUser);
        } else {
            throw new Exception('addDbUserWithSecret : empty email');
        }
    }

    /**
     * Create a new user.
     *
     * @param string $username       : email
     * @param string $password       : password
     * @param string $salt           : private salt
     * @param string $role           : role none|editor|admin
     * @param string $secretQuestion : secret question (encoded)
     * @param string $secretResponse : secret question (encrypted)
     * @param string $mode           : create|update
     */
    public function createUserWithSecret(string $username, string $emailParam, string $password, string $secretQuestion, string $secretResponse, string $mode)
    {
        $email = strtolower($emailParam);

        $error_msg = null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // invalid email
            $error_msg .= 'InvalidEmail ';
        }

        if (empty($password)) {
            $error_msg .= 'EmptyPassword ';
        }

        // Cf forms.js
        // Ensure password length
        if (strlen($password) > 128) {
            $error_msg .= 'InvalidPassword ';
        }

        $file = $this->getJsonUserFile($email);

        if ($mode === 'create') {
            if (empty($username)) {
                $error_msg .= 'InvalidUser ';
            }

            if (file_exists($file)) {
                // user already exists
                $error_msg .= 'AlreadyExists.';
            }
        }

        if (empty($error_msg)) {
            // create a random for this user
            // http://php.net/manual/fr/function.random-bytes.php
            $random_salt = base64_encode(random_bytes($this->saltlength));

            // http://stackoverflow.com/questions/8952807/openssl-digest-vs-hash-vs-hash-hmac-difference-between-salt-hmac
            // store a salted password

            $options = [
                    'cost' => $this->passwordHashCost,
            ];
            $saltpassword = password_hash($password, PASSWORD_BCRYPT, $options);

            // store a salted response
            $saltresponse = '';
            if (!empty($secretResponse)) {
                $saltresponse = password_hash($secretResponse, PASSWORD_BCRYPT, $options);
            }

            if ($mode === 'create') {
                // create user
                $this->addDbUserWithSecret($email, $username, $saltpassword, $random_salt, 'guest', $secretQuestion, $saltresponse);
            } else {
                //role is not modified
                $this->updateUser($email, '', $saltpassword, $random_salt, '');
            }
        }

        return $error_msg;
    }

    /**
     * Authenticate
     * return an empty string if success.
     *
     * @param string $emailParam : email
     * @param string $password   : pseudo clear password (must be hashed from client)
     *
     * @return string return an empty string if success.
     */
    public function login($emailParam, $password)
    {
        $loginmsg = '';

        $debug = false;
        $debugmsg = 'debugmsg ';

        // if someone forgot to do this before
        $email = strtolower($emailParam);

        // return the existing user
        $user = $this->getJsonUser($email);

        // user found
        if (!empty($user)) {
            if (password_verify($password, $user->{'password'})) {
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
     * Authenticate and return a User object with a token.
     *
     * @param string $emailParam : email
     * @param string $password   : pseudo clear password (must be hashed from client)
     *
     * @return Response object
     */
    public function getToken($emailParam, $password): Response
    {
        // initialize Response
        $response = new Response();
        $response->setCode(401);
        $response->setResult('{}');

        $loginmsg = 'Wrong login';

        $debug = false;
        $debugmsg = 'debugmsg ';

        // if someone forgot to do this before
        $email = strtolower($emailParam);

        // return the existing user
        $user = $this->getJsonUser($email);

        // user found
        if (!empty($user)) {
            if (password_verify($password, $user->{'password'})) {
                $jwt = new JwtToken();

                $token = $jwt->createTokenFromUser($user->{'name'}, $user->{'email'}, $user->{'role'}, $user->{'salt'});
                unset($jwt);
                if (isset($token)) {
                    $response->setCode(200);
                    $userResponse = json_decode('{"username":"", "email":"",  "role":"", "clientalgorithm":"", "newpasswordrequired":"", "token":""}');
                    $userResponse->{'name'} = $user->{'name'};
                    $userResponse->{'username'} = $user->{'name'};
                    $userResponse->{'email'} = $user->{'email'};
                    $userResponse->{'role'} = $user->{'role'};
                    $userResponse->{'clientalgorithm'} = $user->{'clientalgorithm'};
                    $userResponse->{'newpasswordrequired'} = $user->{'newpasswordrequired'};

                    $userResponse->{'token'} = $token;

                    $response->setResult(json_encode($userResponse));
                    // success
                    $loginmsg = '';
                }
            } else {
                // incorrect password
                $loginmsg = 'wrong passsword';
            }
        } else {
            // wrong user
            $loginmsg = 'wrong user ' . $email;
            $debugmsg .= $email;
        }

        // return an empty string on success, so if debug is enabled, it's impossible to connect
        if ($debug) {
            $loginmsg .= $debugmsg;
        }

        return $response;
    }

    /**
     * Authenticate and return a User object with a token.
     *
     * @param string $emailParam  : email
     * @param string $password    : pseudo clear password (must be hashed from client)
     * @param string $newPassword : pseudo clear password (must be hashed from client)
     *
     * @return Response object
     */
    public function changePassword($emailParam, $password, $newPassword): Response
    {
        // initialize Response
        $response = new Response();
        $response->setCode(401);

        $response->setResult('{}');

        $loginmsg = 'Wrong login';

        $debug = false;
        $debugmsg = 'debugmsg ';

        // if someone forgot to do this before
        $email = strtolower($emailParam);

        // return the existing user
        $user = $this->getJsonUser($email);

        // user found
        if (!empty($user)) {
            if ($this->login($email, $password) === '') {
                $updateMsg = $this->createUserWithSecret('', $email, $newPassword, '', '', 'update');

                // return the existing user
                $user = $this->getJsonUser($email);
                $user->{'clientalgorithm'} = 'hashmacbase64';
                $user->{'newpasswordrequired'} = 'false';
                JsonUtils::writeJsonFile($this->getJsonUserFile($email), $user);

                if (empty($updateMsg)) {
                    $response = $this->getPublicInfo($email);
                } else {
                    $response->setError(500, 'createUserWithSecret error ' . $updateMsg);
                }
            } else {
                // incorrect password
                $loginmsg = 'wrong passsword';
            }
        } else {
            // wrong user
            $loginmsg = 'wrong user ' . $email;
            $debugmsg .= $email;
        }

        // return an empty string on success, so if debug is enabled, it's impossible to connect
        if ($debug) {
            $loginmsg .= $debugmsg;
        }

        return $response;
    }

    /**
     * Verify token.
     *
     * @param string $token : token
     * @param string $role  : role (editor ...)
     *
     * @return Response object
     */
    public function verifyToken($token, $requiredRole): Response
    {
        $response = $this->getDefaultResponse();

        if (!isset($token)) {
            throw new Exception('empty token');
        }

        $jwt = new JwtToken();

        // get payload and convert to JSON

        $payload = $jwt->getPayload($token);

        if (!isset($payload)) {
            throw new Exception('empty payload');
        }

        $payloadJson = json_decode($payload);
        if (!isset($payloadJson)) {
            throw new Exception('empty payload');
        }
        // get the existing user

        $user = $this->getJsonUser($payloadJson->{'sub'});

        // verify token with secret
        if ($jwt->verifyToken($token, $user->{'salt'})) {
            if ($this->isPermitted($user, $requiredRole)) {
                $response->setCode(200);
            } else {
                // TODO : return 401 instead of 403 ?
                $response->setError(403, 'wrong role');
            }
        } else {
            $response->setError(401, 'verifyToken false');
        }

        return $response;
    }

    /**
     * Control if the current user has access to API.
     *
     * @param stdClass $user         object
     * @param string   $requiredRole required role
     *
     * @return true if access is authorized
     */
    private function isPermitted(stdClass $user, string $requiredRole): bool
    {
        $result = false;
        if (!empty($user) && !empty($user->{'role'}) && !empty($requiredRole)) {
            if ($requiredRole === 'editor') {
                $result = $this->isPermittedEditor($user);
            }

            if ($requiredRole === 'admin') {
                $result = $this->isPermittedAdmin($user);
            }
        }

        return $result;
    }

    /**
     * Control if the current user has access to an editor API.
     *
     * @param stdClass user object
     *
     * @return true if access is authorized
     */
    private function isPermittedEditor(stdClass $user): bool
    {
        $result = false;
        if (!empty($user) && !empty($user->{'role'})) {
            if ($user->{'role'} === 'editor') {
                $result = true;
            } elseif ($user->{'role'} === 'admin') {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Control if the current user has access to an admin API.
     *
     * @param stdClass user object
     *
     * @return true if access is authorized
     */
    private function isPermittedAdmin($user): bool
    {
        $result = false;
        if (!empty($user) && !empty($user->{'role'})) {
            if ($user->{'role'} === 'admin') {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Authenticate and return a User object with a token.
     *
     * @param string $emailParam  email
     * @param string $newPassword new password
     *
     * @return Response object
     */
    public function resetPassword($emailParam, $newPassword): Response
    {
        // initialize Response
        $response = new Response();
        $response->setCode(401);

        $response->setResult('{}');

        $loginmsg = 'Wrong login';

        $debug = false;
        $debugmsg = 'debugmsg ';

        // if someone forgot to do this before
        $email = strtolower($emailParam);

        // return the existing user
        $user = $this->getJsonUser($email);

        // user found
        if (!empty($user)) {
            $updateMsg = $this->createUserWithSecret('', $emailParam, $newPassword, '', '', 'update');

            // return the existing user
            $user = $this->getJsonUser($email);
            $user->{'clientalgorithm'} = 'none';
            $user->{'newpasswordrequired'} = 'true';
            JsonUtils::writeJsonFile($this->getJsonUserFile($email), $user);

            if (empty($updateMsg)) {
                $response = $this->getPublicInfo($email);
            } else {
                $response->setError(500, 'createUserWithSecret error ' . $updateMsg);
            }
        } else {
            // wrong user
            $loginmsg = 'wrong user ' . $email;
            $debugmsg .= $email;
        }

        // return an empty string on success, so if debug is enabled, it's impossible to connect
        if ($debug) {
            $loginmsg .= $debugmsg;
        }

        return $response;
    }

    /**
     * Public info of a user. Beware when doing updates.
     *
     * @param string $email user
     *
     * @return Response public info
     */
    public function getPublicInfo($email): Response
    {
        $response = $this->getDefaultResponse();

        $user = $this->getJsonUser($email);
        if (isset($user)) {
            // do not send private info such as password ...
            $info = json_decode('{"name":"", "clientalgorithm":"", "newpasswordrequired":""}');
            JsonUtils::copy($user, $info);
            $response->setResult(json_encode($info));
            $response->setCode(200);
        }

        return $response;
    }

    /**
     * Generate a US-ASCII random string.
     *
     * @param int $length to generate
     *
     * @return string US-ASCII random string
     */
    public function generateRandomString(int $length = 10): string
    {
        return base64_encode(random_bytes($length));
    }

    /**
     * Initialize a default Response object.
     *
     * @return Response object
     */
    protected function getDefaultResponse() : Response
    {
        $response = new Response();
        $response->setCode(400);
        $response->setResult('{}');

        return $response;
    }
}
