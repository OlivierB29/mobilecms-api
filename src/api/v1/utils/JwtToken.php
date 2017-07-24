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
     */
    public function setAlgorithm($newval): string
    {
        $this->algorithm = $newval;
    }

    /**
     * current algorithm.
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * create a new token.
     */
    public function createTokenFromUser($username, $email, $role, $secretKey): string
    {
        return $this->createToken($this->initHeader(), $this->initPayload($username, $email, $role), $secretKey);
    }

    /**
     * verify a token.
     */
    public function verifyToken($token, $secretKey): bool
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

    public function getPayload($token): string
    {
        $result = '';
        $tokenArray = explode('.', $token);

        if (count($tokenArray) == 3) {
            $result = base64_decode($tokenArray[1]);
        }

        return $result;
    }

    private function initHeader(): string
    {
        return base64_encode('{ "alg": "'.$this->algorithm.'","typ": "JWT"}');
    }

    private function initPayload(string $username, string $email, string $role): string
    {
        return base64_encode('{ "sub": "'.$email.'", "name": "'.$username.'", "role": "'.$role.'"}');
    }

    /**
     * Concat token fields.
     */
    private function createToken(string $header, string $payload, string $secretKey): string
    {
        return $header.'.'.$payload.'.'.$this->createSignature($header, $payload, $secretKey);
    }

    /**
     * create a signature.
     */
    private function createSignature(string $header, string $payload, string $secretKey): string
    {
        return hash_hmac($this->algorithm, $header.'.'.$payload, $this->createSecret($secretKey));
    }

    /**
     * create secret.
     * This implementation create a valid secret for the current day.
     */
    private function createSecret(string $secret): string
    {
        return $secret.date('Yz');
    }

    private function parseHeader(string $payload): string
    {
        return json_decode(base64_decode($payload));
    }

    private function parsePayload(string $payload): string
    {
        return json_decode(base64_decode($payload));
    }
}
