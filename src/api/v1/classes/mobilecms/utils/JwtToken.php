<?php namespace mobilecms\utils;

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
     * @param string $newval algorithm
     */
    public function setAlgorithm($newval): string
    {
        $this->algorithm = $newval;
    }

    /**
     * Current algorithm.
     *
     * @return string algorithm
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * Create a new token.
     *
     * @param string $username  user
     * @param string $email     email
     * @param string $role      role
     * @param string $secretKey secret key
     */
    public function createTokenFromUser(string $username, string $email, string $role, string $secretKey): string
    {
        return $this->createToken($this->initHeader(), $this->initPayload($username, $email, $role), $secretKey);
    }

    /**
     * Verify a token.
     *
     * @param string $token     token data
     * @param string $secretKey secret key
     *
     * @return bool true if success
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
     * Parse payload.
     *
     * @param string $token data
     *
     * @return string payload part
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
     * Default header.
     *
     * @return string default header
     */
    private function initHeader(): string
    {
        return base64_encode('{ "alg": "' . $this->algorithm . '","typ": "JWT"}');
    }

    /**
     * Init payload with user.
     *
     * @param string $username username
     * @param string $email    email
     * @param string $role     role
     *
     * @return string default payload
     */
    private function initPayload(string $username, string $email, string $role): string
    {
        return base64_encode('{ "sub": "' . $email . '", "name": "' . $username . '", "role": "' . $role . '"}');
    }

    /**
     * Concat token fields.
     *
     * @param string $header    header
     * @param string $payload   payload
     * @param string $secretKey secretkey
     *
     * @return string default token
     */
    private function createToken(string $header, string $payload, string $secretKey): string
    {
        return $header . '.' . $payload . '.' . $this->createSignature($header, $payload, $secretKey);
    }

    /**
     * Create a signature.
     *
     * @param string $header    header
     * @param string $payload   payload
     * @param string $secretKey secretkey
     *
     * @return string signature data
     */
    private function createSignature(string $header, string $payload, string $secretKey): string
    {
        return hash_hmac($this->algorithm, $header . '.' . $payload, $this->createSecret($secretKey));
    }

    /**
     * Create secret.
     * This implementation create a valid secret for the current day.
     *
     * @param string $secret secret
     *
     * @return string secret and date
     */
    private function createSecret(string $secret): string
    {
        return $secret . date('Yz');
    }

    /**
     * Parse header.
     *
     * @param string $payload encoded JSON
     *
     * @return JSON payload object
     */
    private function parseHeader(string $payload): string
    {
        return json_decode(base64_decode($payload));
    }

    /**
     * Parse payload.
     *
     * @param string $payload encoded JSON
     *
     * @return string JSON payload object
     */
    private function parsePayload(string $payload): string
    {
        return json_decode(base64_decode($payload));
    }
}
