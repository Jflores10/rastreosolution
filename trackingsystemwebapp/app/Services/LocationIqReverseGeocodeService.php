<?php

namespace App\Services;

use App\Geocache;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Reverse geocoding LocationIQ con caché MongoDB (geocache) y coordenadas redondeadas.
 */
class LocationIqReverseGeocodeService
{
    /** Precisión de redondeo (~111 m) para agrupar consultas cercanas */
    const COORD_DECIMALS = 3;

    /**
     * Precisión de las direcciones en tiempo real (~11 m). Sus registros en geocache
     * llevan `precision` = 4; los de 3 decimales (reporte, push) no tienen ese campo.
     */
    const COORD_DECIMALS_EXACTA = 4;

    /** Una dirección en tiempo real ya guardada sirve para puntos a esta distancia. */
    const RADIO_REUSO_METROS = 20;

    /** Tras un 429 de LocationIQ no se le consulta durante este tiempo (minutos). */
    const PAUSA_LIMITE_MINUTOS = 1;
    const CACHE_PAUSA_LIMITE = 'locationiq_pausa_limite';

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
     * Dirección en tiempo real: consulta LocationIQ con el punto exacto del GPS (no el
     * centro de la celda) y cachea por celdas de ~11 m. El texto empieza por la calle.
     *
     * @param float|int|string|null $lat
     * @param float|int|string|null $lng
     * @return string|null
     */
    public function reverseDireccionExacta($lat, $lng)
    {
        $rounded = $this->roundCoordinates($lat, $lng, self::COORD_DECIMALS_EXACTA);
        if ($rounded === null) {
            return null;
        }

        list($latR, $lngR) = $rounded;

        $cached = $this->direccionExactaCacheada($lat, $lng);
        if ($cached !== null) {
            return $cached;
        }

        // LocationIQ respondió 429 hace poco: no se insiste hasta que pase la pausa.
        if (Cache::has(self::CACHE_PAUSA_LIMITE)) {
            return null;
        }

        $json = $this->requestLocationIq((float) $lat, (float) $lng);
        $direccion = $json === null ? null : $this->direccionDesdeCalle($json);
        if ($direccion === null || $direccion === '') {
            return null;
        }

        $this->saveToCache($latR, $lngR, $direccion, self::COORD_DECIMALS_EXACTA);

        return $direccion;
    }

    /**
     * Dirección en tiempo real solo desde geocache, sin llamar a LocationIQ: la de la
     * celda de ~11 m o, si no hay, la guardada más cercana a ≤ RADIO_REUSO_METROS.
     *
     * @param float|int|string|null $lat
     * @param float|int|string|null $lng
     * @return string|null
     */
    public function direccionExactaCacheada($lat, $lng)
    {
        $rounded = $this->roundCoordinates($lat, $lng, self::COORD_DECIMALS_EXACTA);
        if ($rounded === null) {
            return null;
        }

        list($latR, $lngR) = $rounded;

        $cached = $this->findInCache($latR, $lngR, self::COORD_DECIMALS_EXACTA);
        if ($cached !== null) {
            return $cached;
        }

        return $this->findCercanaEnCache((float) $lat, (float) $lng);
    }

    /**
     * @param float $lat
     * @param float $lng
     * @return string|null
     */
    protected function findCercanaEnCache($lat, $lng)
    {
        // Caja de ~22 m alrededor del punto; luego se queda la más cercana dentro del radio.
        $delta = 0.0002;
        try {
            $rows = Geocache::where('precision', self::COORD_DECIMALS_EXACTA)
                ->where('lat', '>=', $lat - $delta)->where('lat', '<=', $lat + $delta)
                ->where('lng', '>=', $lng - $delta)->where('lng', '<=', $lng + $delta)
                ->limit(30)
                ->get();
        } catch (\Exception $e) {
            Log::warning('Geocache cercana: '.$e->getMessage(), array('lat' => $lat, 'lng' => $lng));
            return null;
        }

        $mejor = null;
        $mejorDistancia = self::RADIO_REUSO_METROS;
        $cosLat = cos(deg2rad($lat));
        foreach ($rows as $row) {
            $dir = isset($row->direccion) ? trim((string) $row->direccion) : '';
            if ($dir === '') {
                continue;
            }
            $dy = ((float) $row->lat - $lat) * 111320;
            $dx = ((float) $row->lng - $lng) * 111320 * $cosLat;
            $distancia = sqrt($dx * $dx + $dy * $dy);
            if ($distancia <= $mejorDistancia) {
                $mejorDistancia = $distancia;
                $mejor = $dir;
            }
        }

        return $mejor;
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
     * @param int $decimales
     * @return array|null [lat, lng] redondeados
     */
    public function roundCoordinates($lat, $lng, $decimales = self::COORD_DECIMALS)
    {
        if ($lat === null || $lng === null || $lat === '' || $lng === '') {
            return null;
        }

        $la = round((float) $lat, $decimales);
        $lo = round((float) $lng, $decimales);

        if (!is_finite($la) || !is_finite($lo) || abs($la) > 90 || abs($lo) > 180) {
            return null;
        }

        return array($la, $lo);
    }

    /**
     * Calle primero ("Av. Paseo 18 123, Barrio, Ciudad"); sin calle, display_name.
     *
     * @param array $json respuesta de LocationIQ (normalizeaddress=1)
     * @return string|null
     */
    protected function direccionDesdeCalle(array $json)
    {
        $a = isset($json['address']) && is_array($json['address']) ? $json['address'] : array();
        $campo = function ($k) use ($a) {
            return isset($a[$k]) ? trim((string) $a[$k]) : '';
        };

        $calle = $campo('road');
        if ($calle !== '') {
            $partes = array(trim($calle.' '.$campo('house_number')));
            $barrio = $campo('neighbourhood') !== '' ? $campo('neighbourhood') : $campo('suburb');
            if ($barrio !== '') {
                $partes[] = $barrio;
            }
            $ciudad = $campo('city');
            if ($ciudad !== '' && $ciudad !== $barrio) {
                $partes[] = $ciudad;
            }
            return implode(', ', $partes);
        }

        $display = isset($json['display_name']) ? trim((string) $json['display_name']) : '';
        return $display === '' ? null : $display;
    }

    /**
     * @param float $latR
     * @param float $lngR
     * @param int|null $precision null: registros de 3 decimales (sin campo precision)
     * @return string|null
     */
    protected function findInCache($latR, $lngR, $precision = null)
    {
        try {
            $query = Geocache::where('lat', $latR)->where('lng', $lngR);
            if ($precision === null) {
                $query->where('precision', 'exists', false);
            } else {
                $query->where('precision', $precision);
            }
            $row = $query->first();
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
     * @param int|null $precision
     */
    protected function saveToCache($latR, $lngR, $direccion, $precision = null)
    {
        $direccion = trim((string) $direccion);
        if ($direccion === '') {
            return;
        }

        try {
            $query = Geocache::where('lat', $latR)->where('lng', $lngR);
            if ($precision === null) {
                $query->where('precision', 'exists', false);
            } else {
                $query->where('precision', $precision);
            }
            $existing = $query->first();
            if ($existing) {
                if (trim((string) $existing->direccion) === '') {
                    $existing->direccion = $direccion;
                    $existing->save();
                }
                return;
            }

            $datos = array(
                'lat' => $latR,
                'lng' => $lngR,
                'direccion' => $direccion,
                'createdAt' => Carbon::now(),
            );
            if ($precision !== null) {
                $datos['precision'] = $precision;
            }
            Geocache::create($datos);
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
        $json = $this->requestLocationIq($latR, $lngR);
        if ($json === null) {
            return null;
        }
        if (empty($json['display_name'])) {
            Log::debug('LocationIQ: sin display_name', array('lat' => $latR, 'lng' => $lngR));
            return null;
        }

        return trim((string) $json['display_name']);
    }

    /**
     * @param float $lat
     * @param float $lng
     * @return array|null respuesta JSON de LocationIQ o null si falla
     */
    protected function requestLocationIq($lat, $lng)
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
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'json',
                    'accept-language' => 'es',
                    'addressdetails' => 1,
                    'normalizeaddress' => 1,
                ),
                'http_errors' => false,
            ));

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();

            if ($status === 429) {
                Log::warning('LocationIQ rate limit (429)', array('lat' => $lat, 'lng' => $lng));
                Cache::put(self::CACHE_PAUSA_LIMITE, true, self::PAUSA_LIMITE_MINUTOS);
                return null;
            }

            if ($status !== 200) {
                Log::warning('LocationIQ HTTP '.$status, array(
                    'lat' => $lat,
                    'lng' => $lng,
                    'body' => substr($body, 0, 300),
                ));
                return null;
            }

            $json = json_decode($body, true);
            if (!is_array($json)) {
                Log::warning('LocationIQ: JSON invalido', array('lat' => $lat, 'lng' => $lng));
                return null;
            }
            if (isset($json['error'])) {
                Log::warning('LocationIQ API error', array('lat' => $lat, 'lng' => $lng, 'error' => $json['error']));
                return null;
            }

            return $json;
        } catch (\Exception $e) {
            Log::warning('LocationIQ exception: '.$e->getMessage(), array('lat' => $lat, 'lng' => $lng));
            return null;
        }
    }
}
