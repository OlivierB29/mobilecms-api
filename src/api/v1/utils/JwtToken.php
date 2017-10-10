<?php
/**
 * Utility for creating JWT.
 */
class JwtToken
{
    /**
     * algorithm see http://php.net/manual/en/function.hash-algos.php.
     */
    private $algorithm = 'sha512';

    /**
     * set algorithm see http://php.net/manual/en/function.hash-algos.php.
     *
     * @param newval algorithm
     */
    public function setAlgorithm($newval): string
    {
        $this->algorithm = $newval;
    }

    /**
     * current algorithm.
     *
     * @return algorithm
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * create a new token.
     *
     * @param username user
     * @param email email
     * @param role role
     * @param secretKey secret key
     */
    public function createTokenFromUser(string $username, string $email, string $role, string $secretKey): string
    {
        return $this->createToken($this->initHeader(), $this->initPayload($username, $email, $role), $secretKey);
    }

    /**
     * verify a token.
     *
     * @param token token data
     * @param secretKey secret key
     * @param success or error
     */
    public function verifyToken(string $token, string $secretKey): bool
    {
        $result = false;

        $tokenArray = explode('.', $token);

        if (count($tokenArray) == 3) {
            $header = $tokenArray[0];
            $payload = $tokenArray[1];
            $signatureFromToken = $tokenArray[2];

            $computedSignature = $this->createSignature($header, $payload, $secretKey);

            $result = hash_equals($signatureFromToken, $computedSignature);
        }

        return $result;
    }

    /**
     * @param token token data
     *
     * @return payload part
     */
    public function getPayload(string $token): string
    {
        $result = '';
        $tokenArray = explode('.', $token);

        if (count($tokenArray) == 3) {
            $result = base64_decode($tokenArray[1]);
        }

        return $result;
    }

    /**
     * @return default header
     */
    private function initHeader(): string
    {
        return base64_encode('{ "alg": "'.$this->algorithm.'","typ": "JWT"}');
    }

    /**
     * init payload with user.
     *
     * @param username username
     * @param email email
     * @param role role
     *
     * @return default payload
     */
    private function initPayload(string $username, string $email, string $role): string
    {
        return base64_encode('{ "sub": "'.$email.'", "name": "'.$username.'", "role": "'.$role.'"}');
    }

    /**
     * Concat token fields.
     *
     * @param header header
     * @param payload payload
     * @param secretKey secretkey
     *
     * @return default token
     */
    private function createToken(string $header, string $payload, string $secretKey): string
    {
        return $header.'.'.$payload.'.'.$this->createSignature($header, $payload, $secretKey);
    }

    /**
     * create a signature.
     *
     * @param header header
     * @param payload payload
     * @param secretKey secretkey
     *
     * @return signature data
     */
    private function createSignature(string $header, string $payload, string $secretKey): string
    {
        return hash_hmac($this->algorithm, $header.'.'.$payload, $this->createSecret($secretKey));
    }

    /**
     * create secret.
     * This implementation create a valid secret for the current day.
     *
     * @param secret secret
     *
     * @return secret and date
     */
    private function createSecret(string $secret): string
    {
        return $secret.date('Yz');
    }

    /**
     * parse header.
     *
     * @param payload encoded JSON
     *
     * @return JSON payload object
     */
    private function parseHeader(string $payload): string
    {
        return json_decode(base64_decode($payload));
    }

    /**
     * parse payload.
     *
     * @param payload encoded JSON
     *
     * @return JSON payload object
     */
    private function parsePayload(string $payload): string
    {
        return json_decode(base64_decode($payload));
    }
}
