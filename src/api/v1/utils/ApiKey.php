<?php

require_once 'JsonUtils.php';

/*
 * API Key verification for CORS
 * TODO hash impl
 */
class ApiKey
{
    /**
     * Basic implementation of API Key verification.
     */
    public function verifyKey($keyfile, $key, $origin): bool
    {
        $result = false;
        $jsonKey = JsonUtils::readJsonFile($keyfile);
        if (isset($jsonKey) && isset($key) && isset($origin)) {
            if (strlen($jsonKey->{'key'}) === 0) {
                throw new Exception('Empty configuration API Key value');
            }
            if (strlen($jsonKey->{'origin'}) === 0) {
                throw new Exception('Empty configuration API Key origin');
            }

            $result = ($jsonKey->{'key'} === $key && $jsonKey->{'origin'} === $origin);
        }


        return $result;
    }
}
