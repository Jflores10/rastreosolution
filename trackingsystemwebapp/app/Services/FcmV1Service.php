<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

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
     * Envía FCM HTTP v1. El campo notification.body es SIEMPRE texto plano en Android/iOS (no hay HTML).
     * Si el cuerpo trae etiquetas HTML, se envía además data.html_body con el HTML original para que la app
     * pinte el icono (p. ej. Html.fromHtml en Android) al mostrar o al expandir la notificación.
     *
     * @param string $deviceToken
     * @param string $title
     * @param string $body
     * @return array success(bool), deactivate_token(bool)
     */
    public function sendToDevice($deviceToken, $title, $body)
    {
        $account = $this->loadServiceAccount();
        if (!$account) {
            return array('success' => false, 'deactivate_token' => false);
        }
        $access = $this->getAccessToken($account);
        if (!$access) {
            return array('success' => false, 'deactivate_token' => false);
        }
        // Permite forzar project_id desde .env cuando el JSON pertenece a otra cuenta/proyecto.
        $projectId = trim((string) env('FIREBASE_PROJECT_ID', $account['project_id']));
        if ($projectId === '') {
            $projectId = $account['project_id'];
        }
        $url = 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/messages:send';

        $hasHtml = (stripos($body, '<') !== false && stripos($body, '>') !== false);
        $plainBody = $body;
        if ($hasHtml) {
            $plainBody = trim(preg_replace('/\s+/', ' ', strip_tags($body)));
            if ($plainBody === '') {
                $plainBody = 'Aviso';
            }
        }

        // Siempre enviar data + notification.
        // - notification: bandeja del sistema (app cerrada/background)
        // - data: permite que la app en foreground pinte preview local (estilo WhatsApp)
        $dataPayload = array(
            'title' => (string) $title,
            'body' => (string) $plainBody,
            'preview' => (string) $plainBody,
            'sent_at' => (string) time(),
            'channel' => 'alerts',
        );
        if ($hasHtml) {
            $dataPayload['html_body'] = (string) $body;
        }

        $message = array(
            'token' => $deviceToken,
            'notification' => array(
                'title' => $title,
                'body' => $plainBody,
            ),
            'data' => $dataPayload,
            'android' => array(
                'priority' => 'HIGH',
                'notification' => array(
                    // Este channel_id debe existir en la app móvil con IMPORTANCE_HIGH
                    'channel_id' => 'alerts',
                    'sound' => 'default',
                    'default_vibrate_timings' => true,
                    'notification_priority' => 'PRIORITY_MAX',
                ),
            ),
            'apns' => array(
                'headers' => array(
                    'apns-priority' => '10',
                    'apns-push-type' => 'alert',
                ),
                'payload' => array(
                    'aps' => array(
                        'sound' => 'default',
                        'content-available' => 1,
                    ),
                ),
            ),
        );

        $payload = array(
            'message' => $message,
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
            return array('success' => true, 'deactivate_token' => false);
        } catch (RequestException $e) {
            $responseBody = '';
            if ($e->hasResponse()) {
                $responseBody = (string) $e->getResponse()->getBody();
            }

            $tokenHint = (strlen((string) $deviceToken) > 16)
                ? ('...' . substr((string) $deviceToken, -12))
                : (string) $deviceToken;

            $deactivateToken = false;
            if ($responseBody !== '') {
                if (stripos($responseBody, 'UNREGISTERED') !== false
                    || stripos($responseBody, 'registration-token-not-registered') !== false
                    || stripos($responseBody, 'not a valid FCM registration token') !== false) {
                    $deactivateToken = true;
                }
            }

            \Log::warning('FCM send error', array(
                'project_id' => $projectId,
                'client_email' => isset($account['client_email']) ? $account['client_email'] : null,
                'token_hint' => $tokenHint,
                'message' => $e->getMessage(),
                'response' => $responseBody,
            ));
            return array('success' => false, 'deactivate_token' => $deactivateToken);
        } catch (\Exception $e) {
            \Log::warning('FCM send error: ' . $e->getMessage(), array(
                'project_id' => $projectId,
                'client_email' => isset($account['client_email']) ? $account['client_email'] : null,
            ));
            return array('success' => false, 'deactivate_token' => false);
        }
    }
}
