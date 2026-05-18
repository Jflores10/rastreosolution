<?php

namespace App\Services;

use App\Geocache;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Reverse geocoding LocationIQ con caché MongoDB (geocache) y coordenadas redondeadas.
 */
class LocationIqReverseGeocodeService
{
    /** Precisión de redondeo (~111 m) para agrupar consultas cercanas */
    const COORD_DECIMALS = 3;

    /**
     * @param float|int|string|null $lat
     * @param float|int|string|null $lng
     * @return string|null display_name / direccion o null
     */
    public function reverseDisplayName($lat, $lng)
    {
        $rounded = $this->roundCoordinates($lat, $lng);
        if ($rounded === null) {
            return null;
        }

        list($latR, $lngR) = $rounded;

        $cached = $this->findInCache($latR, $lngR);
        if ($cached !== null) {
            return $cached;
        }

        $direccion = $this->fetchFromLocationIq($latR, $lngR);
        if ($direccion === null || $direccion === '') {
            return null;
        }

        $this->saveToCache($latR, $lngR, $direccion);

        return $direccion;
    }

    /**
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

    /**
     * @param float|int|string|null $lat
     * @param float|int|string|null $lng
     * @return array|null [lat, lng] redondeados
     */
    public function roundCoordinates($lat, $lng)
    {
        if ($lat === null || $lng === null || $lat === '' || $lng === '') {
            return null;
        }

        $la = round((float) $lat, self::COORD_DECIMALS);
        $lo = round((float) $lng, self::COORD_DECIMALS);

        if (!is_finite($la) || !is_finite($lo) || abs($la) > 90 || abs($lo) > 180) {
            return null;
        }

        return array($la, $lo);
    }

    /**
     * @param float $latR
     * @param float $lngR
     * @return string|null
     */
    protected function findInCache($latR, $lngR)
    {
        try {
            $row = Geocache::where('lat', $latR)->where('lng', $lngR)->first();
            if (!$row) {
                return null;
            }
            $dir = isset($row->direccion) ? trim((string) $row->direccion) : '';
            if ($dir === '') {
                return null;
            }

            return $dir;
        } catch (\Exception $e) {
            Log::warning('Geocache lectura: '.$e->getMessage(), array('lat' => $latR, 'lng' => $lngR));
            return null;
        }
    }

    /**
     * @param float $latR
     * @param float $lngR
     * @param string $direccion
     */
    protected function saveToCache($latR, $lngR, $direccion)
    {
        $direccion = trim((string) $direccion);
        if ($direccion === '') {
            return;
        }

        try {
            $existing = Geocache::where('lat', $latR)->where('lng', $lngR)->first();
            if ($existing) {
                if (trim((string) $existing->direccion) === '') {
                    $existing->direccion = $direccion;
                    $existing->save();
                }
                return;
            }

            Geocache::create(array(
                'lat' => $latR,
                'lng' => $lngR,
                'direccion' => $direccion,
                'createdAt' => Carbon::now(),
            ));
        } catch (\Exception $e) {
            // Carrera: otro proceso pudo insertar la misma clave
            Log::debug('Geocache guardado: '.$e->getMessage(), array('lat' => $latR, 'lng' => $lngR));
        }
    }

    /**
     * @param float $latR coordenadas ya redondeadas
     * @param float $lngR
     * @return string|null
     */
    protected function fetchFromLocationIq($latR, $lngR)
    {
        $apiKey = trim((string) env('LOCATIONIQ_API_KEY', ''));
        if ($apiKey === '') {
            Log::debug('LocationIQ: LOCATIONIQ_API_KEY no configurada');
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
                    'lat' => $latR,
                    'lon' => $lngR,
                    'format' => 'json',
                    'accept-language' => 'es',
                ),
                'http_errors' => false,
            ));

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();

            if ($status === 429) {
                Log::warning('LocationIQ rate limit (429)', array('lat' => $latR, 'lng' => $lngR));
                return null;
            }

            if ($status !== 200) {
                Log::warning('LocationIQ HTTP '.$status, array(
                    'lat' => $latR,
                    'lng' => $lngR,
                    'body' => substr($body, 0, 300),
                ));
                return null;
            }

            $json = json_decode($body, true);
            if (!is_array($json)) {
                Log::warning('LocationIQ: JSON invalido', array('lat' => $latR, 'lng' => $lngR));
                return null;
            }
            if (isset($json['error'])) {
                Log::warning('LocationIQ API error', array('lat' => $latR, 'lng' => $lngR, 'error' => $json['error']));
                return null;
            }
            if (empty($json['display_name'])) {
                Log::debug('LocationIQ: sin display_name', array('lat' => $latR, 'lng' => $lngR));
                return null;
            }

            return trim((string) $json['display_name']);
        } catch (\Exception $e) {
            Log::warning('LocationIQ exception: '.$e->getMessage(), array('lat' => $latR, 'lng' => $lngR));
            return null;
        }
    }
}
