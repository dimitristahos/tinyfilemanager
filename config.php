<?php
require_once 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\JWK;

class ClerkAuth
{
    private $jwksUrl;
    private $issuer;

    public function __construct($issuer = 'https://immune-ocelot-3.clerk.accounts.dev')
    {
        $this->jwksUrl = $issuer . '/.well-known/jwks.json';
        $this->issuer = $issuer;
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

            // Get all keys from JWKS
            $keys = JWK::parseKeySet($jwks);

            // Get the first key's ID
            $firstKeyId = array_key_first($keys);
            if ($firstKeyId === null) {
                throw new Exception("No valid keys found");
            }

            // Return the key material
            return $keys[$firstKeyId]->getKeyMaterial();
        } catch (Exception $e) {
            error_log('Failed to get public key: ' . $e->getMessage());
            return null;
        }
    }

    public function isAuthenticated()
    {
        try {
            if (!isset($_COOKIE['__session'])) {
                return false;
            }

            $token = $_COOKIE['__session'];
            $key = $this->getPublicKey();

            if ($key === null) {
                return false;
            }

            $decoded = JWT::decode($token, new Key($key, 'RS256'));

            // Verify the token issuer
            if ($decoded->iss !== $this->issuer) {
                return false;
            }

            // Verify expiration
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
            if (!isset($_COOKIE['__session'])) {
                return null;
            }

            $token = $_COOKIE['__session'];
            $key = $this->getPublicKey();

            if ($key === null) {
                return null;
            }

            $decoded = JWT::decode($token, new Key($key, 'RS256'));

            return $decoded->sub ?? null;
        } catch (Exception $e) {
            return null;
        }
    }
}

// Authentication check
$clerk = new ClerkAuth('https://immune-ocelot-3.clerk.accounts.dev');

if (!$clerk->isAuthenticated()) {
    header('HTTP/1.0 401 Unauthorized');
    echo "Not authenticated";

    sleep(4);

    header("Location: http://popssh.lndo.site");

    exit();
}

//Default Configuration
$CONFIG = '{"lang":"en","error_reporting":false,"show_hidden":false,"hide_Cols":false,"theme":"light"}';

// User is authenticated, continue with file manager configuration
$use_auth = false; // Since we've already handled auth above

// Root path for file manager
$root_path = $_SERVER['DOCUMENT_ROOT'] . '/data';

// Root url for links in file manager
$root_url = 'http://popx6.gr';
