<?php
require_once 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\JWK;

class ClerkAuth
{
    private $jwksUrl;
    private $issuer;
    private $token;

    public function __construct($issuer = 'https://immune-ocelot-3.clerk.accounts.dev')
    {
        $this->jwksUrl = $issuer . '/.well-known/jwks.json';
        $this->issuer = $issuer;

        // Initialize token from either GET parameter or cookie
        $this->token = $_GET['token'] ?? $_COOKIE['user_session'] ?? null;

        echo $_GET['token'];

        // If token came from GET parameter, set it as a cookie for future requests
        if (isset($_GET['token']) && !empty($_GET['token'])) {
            setcookie('user_session', $_GET['token'], [
                'httponly' => true,
                'secure' => true,
                'samesite' => 'Strict',
                'path' => '/'
            ]);
        }
    }

    private function getPublicKey()
    {
        try {
            $response = file_get_contents($this->jwksUrl);
            if ($response === false) {
                throw new Exception("Failed to fetch JWKS");
            }

            $jwks = json_decode($response, true);
            if (!isset($jwks['keys']) || empty($jwks['keys'])) {
                throw new Exception("No keys found in JWKS");
            }

            $keys = JWK::parseKeySet($jwks);
            $firstKeyId = array_key_first($keys);
            if ($firstKeyId === null) {
                throw new Exception("No valid keys found");
            }

            return $keys[$firstKeyId]->getKeyMaterial();
        } catch (Exception $e) {
            error_log('Failed to get public key: ' . $e->getMessage());
            return null;
        }
    }

    public function isAuthenticated()
    {
        try {
            if ($this->token === null) {
                return false;
            }

            $key = $this->getPublicKey();
            if ($key === null) {
                return false;
            }

            $decoded = JWT::decode($this->token, new Key($key, 'RS256'));

            if ($decoded->iss !== $this->issuer) {
                return false;
            }

            if (isset($decoded->exp) && time() >= $decoded->exp) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            error_log('Authentication error: ' . $e->getMessage());
            return false;
        }
    }

    public function getUserId()
    {
        try {
            if ($this->token === null) {
                return null;
            }

            $key = $this->getPublicKey();
            if ($key === null) {
                return null;
            }

            $decoded = JWT::decode($this->token, new Key($key, 'RS256'));
            return $decoded->sub ?? null;
        } catch (Exception $e) {
            return null;
        }
    }
}

// Initialize authentication
$clerk = new ClerkAuth('https://immune-ocelot-3.clerk.accounts.dev');

if (!$clerk->isAuthenticated()) {
    header('HTTP/1.0 401 Unauthorized');
    echo "Not authenticated";

    // Make sure no output has been sent before this point
    if (!headers_sent()) {
        sleep(4);
        header("Location: http://popssh.lndo.site");
    }
    exit();
}

require_once 'tinyfilemanager.php';
