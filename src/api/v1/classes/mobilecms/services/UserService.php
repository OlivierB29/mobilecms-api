<?php namespace mobilecms\services;

/*
 * Inspired by http://fr.wikihow.com/cr%C3%A9er-un-script-de-connexion-s%C3%A9curis%C3%A9e-avec-PHP-et-MySQL
 * This fork uses JSON as storage data
 */

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

    /**
     * Constructor.
     *
     * @param string $databasedir eg : public
     */
    public function __construct(string $databasedir)
    {
        $this->databasedir = $databasedir;
    }


    public function getAllUsers(): \mobilecms\utils\Response
    {
        $response = $this->getDefaultResponse();

        $thelist = [];

        if ($handle = opendir($this->databasedir)) {
            while (false !== ($file = readdir($handle))) {
                $fileObject = json_decode('{}');
                if ($file != '.' && $file != '..' && strtolower(substr($file, strrpos($file, '.') + 1)) == 'json') {
                    $fileObject->{'filename'} = $file;
                    $fileObject->{'email'} = str_replace('.json', '', $file);
                    array_push($thelist, $fileObject);
                }
            }

            closedir($handle);
        }

        $tmp = json_decode('{}');
        $tmp->{'list'} = $thelist;
        $response->setResult($tmp);
        $response->setCode(200);

        return $response;
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
            throw new \Exception('getJsonUserFile()  empty conf');
        }

        if (!empty($email)) {
            return $this->databasedir . '/' . strtolower($email) . '.json';
        } else {
            throw new \Exception('getJsonUserFile()  empty email');
        }
    }

    /**
     * Return a JSON object of a user, null if not found.
     *
     * @param string $email : email
     *
     * @return \stdClass user object
     */
    public function getJsonUser(string $email): \stdClass
    {
        $result = null;

        // file exists ?
        $file = $this->getJsonUserFile($email);
        if (file_exists($file)) {
            $jsonUser = \mobilecms\utils\JsonUtils::readJsonFile($file);

            if (isset($jsonUser->{'name'}) && isset($jsonUser->{'password'})) {
                $result = $jsonUser;
            } else {
                throw new \Exception('empty user ');
            }
        } else {
            throw new \Exception('not found');
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
            \mobilecms\utils\JsonUtils::writeJsonFile($file, $jsonUser);
            $result = true;
        }

        return $result;
    }


    /**
     * Initialize a default Response object.
     *
     * @return Response object
     */
    protected function getDefaultResponse() : \mobilecms\utils\Response
    {
        $response = new \mobilecms\utils\Response();
        $response->setCode(400);
        $response->setResult(new \stdClass);

        return $response;
    }


    /**
     * Create a new user file.
     *
     * @param string $email          : email
     * @param string $name           : name
     * @param string $password       : password
     * @param string $salt           : private salt
     * @param string $role           : role none|editor|admin
     */
    public function addDbUser(
        string $email,
        string $name,
        string $password,
        string $salt,
        string $role
    ) {
        if (!empty($email)) {
            $jsonUser = json_decode('{}');
            $jsonUser->{'name'} = $name;
            $jsonUser->{'email'} = $email;
            $jsonUser->{'password'} = $password;
            $jsonUser->{'salt'} = $salt;
            $jsonUser->{'role'} = $role;

            $file = $this->getJsonUserFile($email);
            \mobilecms\utils\JsonUtils::writeJsonFile($file, $jsonUser);
        }
    }
}
