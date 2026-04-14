<?php

namespace App\Services;

use GuzzleHttp\Client;

/**
 * Envío FCM HTTP v1 usando cuenta de servicio (JSON) sin SDK de Firebase.
 * Compatible con PHP 5.6 / Laravel 5.3.
 */
class FcmV1Service
{
    /** @var int */
    protected static $cachedExp = 0;

    /** @var string|null */
    protected static $cachedAccessToken;

    /**
     * Ruta al JSON de Firebase (subido desde Configuración).
     *
     * @return string
     */
    public function getServiceAccountPath()
    {
        return storage_path('app/firebase/service-account.json');
    }

    /**
     * @return bool
     */
    public function hasServiceAccountFile()
    {
        $path = $this->getServiceAccountPath();
        return is_file($path) && is_readable($path);
    }

    /**
     * @return array|null
     */
    protected function loadServiceAccount()
    {
        if (!$this->hasServiceAccountFile()) {
            return null;
        }
        $raw = @file_get_contents($this->getServiceAccountPath());
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return null;
        }
        if (empty($data['project_id']) || empty($data['private_key']) || empty($data['client_email'])) {
            return null;
        }
        return $data;
    }

    /**
     * @param string $data
     * @return string
     */
    protected function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param array $account
     * @return string|null
     */
    public function getAccessToken(array $account)
    {
        $now = time();
        if (self::$cachedAccessToken && self::$cachedExp > ($now + 60)) {
            return self::$cachedAccessToken;
        }

        $header = $this->base64UrlEncode(json_encode(array('alg' => 'RS256', 'typ' => 'JWT')));
        $claim = $this->base64UrlEncode(json_encode(array(
            'iss' => $account['client_email'],
            'sub' => $account['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        )));

        $input = $header . '.' . $claim;
        $privateKey = openssl_pkey_get_private($account['private_key']);
        if ($privateKey === false) {
            return null;
        }
        $signature = '';
        if (!openssl_sign($input, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            return null;
        }
        $jwt = $input . '.' . $this->base64UrlEncode($signature);

        try {
            $client = new Client(array('timeout' => 20));
            $res = $client->post('https://oauth2.googleapis.com/token', array(
                'form_params' => array(
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ),
            ));
            $json = json_decode($res->getBody()->getContents(), true);
            if (!is_array($json) || empty($json['access_token'])) {
                return null;
            }
            self::$cachedAccessToken = $json['access_token'];
            self::$cachedExp = $now + (int) (isset($json['expires_in']) ? $json['expires_in'] : 3600);
            return self::$cachedAccessToken;
        } catch (\Exception $e) {
            \Log::warning('FCM OAuth token error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @param string $deviceToken
     * @param string $title
     * @param string $body
     * @return bool
     */
    public function sendToDevice($deviceToken, $title, $body)
    {
        $account = $this->loadServiceAccount();
        if (!$account) {
            return false;
        }
        $access = $this->getAccessToken($account);
        if (!$access) {
            return false;
        }
        $projectId = $account['project_id'];
        $url = 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/messages:send';

        $payload = array(
            'message' => array(
                'token' => $deviceToken,
                'notification' => array(
                    'title' => $title,
                    'body' => $body,
                ),
            ),
        );

        try {
            $client = new Client(array('timeout' => 20));
            $client->post($url, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access,
                    'Content-Type' => 'application/json',
                ),
                'body' => json_encode($payload),
            ));
            return true;
        } catch (\Exception $e) {
            \Log::warning('FCM send error: ' . $e->getMessage());
            return false;
        }
    }
}
