<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Reverse geocoding LocationIQ (solo cuando el llamador va a usar la dirección).
 */
class LocationIqReverseGeocodeService
{
    /**
     * @param float|int|string|null $lat
     * @param float|int|string|null $lng
     * @return string|null display_name o null
     */
    public function reverseDisplayName($lat, $lng)
    {
        $apiKey = trim((string) env('LOCATIONIQ_API_KEY', ''));
        if ($apiKey === '') {
            Log::debug('LocationIQ: LOCATIONIQ_API_KEY no configurada');
            return null;
        }

        if ($lat === null || $lng === null || $lat === '' || $lng === '') {
            return null;
        }

        $la = (float) $lat;
        $lo = (float) $lng;
        if (!is_finite($la) || !is_finite($lo) || abs($la) > 90 || abs($lo) > 180) {
            Log::debug('LocationIQ: coordenadas invalidas', array('lat' => $lat, 'lng' => $lng));
            return null;
        }

        $base = rtrim((string) env('LOCATIONIQ_REVERSE_BASE', 'https://us1.locationiq.com/v1/reverse'), '/');
        $timeoutMs = (int) env('LOCATIONIQ_REVERSE_TIMEOUT_MS', 8000);
        if ($timeoutMs < 500) {
            $timeoutMs = 500;
        }

        try {
            $client = new Client(array(
                'timeout' => $timeoutMs / 1000.0,
                'connect_timeout' => 3.0,
            ));
            $response = $client->get($base, array(
                'query' => array(
                    'key' => $apiKey,
                    'lat' => $la,
                    'lon' => $lo,
                    'format' => 'json',
                    'accept-language' => 'es',
                ),
                'http_errors' => false,
            ));

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();
            if ($status !== 200) {
                Log::warning('LocationIQ HTTP '.$status, array(
                    'lat' => $la,
                    'lng' => $lo,
                    'body' => substr($body, 0, 300),
                ));
                return null;
            }

            $json = json_decode($body, true);
            if (!is_array($json)) {
                Log::warning('LocationIQ: JSON invalido', array('lat' => $la, 'lng' => $lo));
                return null;
            }
            if (isset($json['error'])) {
                Log::warning('LocationIQ API error', array('lat' => $la, 'lng' => $lo, 'error' => $json['error']));
                return null;
            }
            if (empty($json['display_name'])) {
                Log::debug('LocationIQ: sin display_name', array('lat' => $la, 'lng' => $lo));
                return null;
            }

            return trim((string) $json['display_name']);
        } catch (\Exception $e) {
            Log::warning('LocationIQ exception: '.$e->getMessage(), array('lat' => $la, 'lng' => $lo));
            return null;
        }
    }

    /**
     * Añade línea de dirección al cuerpo del push si hay coordenadas y LocationIQ responde.
     *
     * @param string $body
     * @param float|int|string|null $lat
     * @param float|int|string|null $lng
     * @return string
     */
    public function appendDireccionToPushBody($body, $lat, $lng)
    {
        $direccion = $this->reverseDisplayName($lat, $lng);
        if ($direccion === null || $direccion === '') {
            return $body;
        }

        return $body."\n* 📫 Dirección:* ".$direccion;
    }
}
