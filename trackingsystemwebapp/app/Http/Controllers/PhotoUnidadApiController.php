<?php

namespace App\Http\Controllers;

use App\PhotoUnidad;
use App\Unidad;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class PhotoUnidadApiController extends Controller
{
    /**
     * Listado paginado de fotos (JSON). Filtros opcionales por query.
     */
    public function index(Request $request)
    {
        $query = PhotoUnidad::orderBy('fecha', 'desc');

        $cooperativaId = trim((string) $request->query('cooperativa_id', ''));
        $unidadIds = $request->query('unidad_id', array());
        if (!is_array($unidadIds)) {
            $unidadIds = array_filter(array(trim((string) $unidadIds)));
        } else {
            $unidadIds = array_values(array_filter(array_map(function ($v) {
                return trim((string) $v);
            }, $unidadIds)));
        }
        $hoy = Carbon::now()->format('Y-m-d');
        $desde = trim((string) $request->query('desde', $hoy));
        $hasta = trim((string) $request->query('hasta', $hoy));

        try {
            $desdeDate = Carbon::createFromFormat('Y-m-d', $desde)->startOfDay();
        } catch (\Exception $e) {
            $desdeDate = Carbon::now()->startOfDay();
            $desde = $desdeDate->format('Y-m-d');
        }

        try {
            $hastaDate = Carbon::createFromFormat('Y-m-d', $hasta)->endOfDay();
        } catch (\Exception $e) {
            $hastaDate = Carbon::now()->endOfDay();
            $hasta = $hastaDate->format('Y-m-d');
        }

        if ($hastaDate->lt($desdeDate)) {
            $tmp = $desdeDate->copy();
            $desdeDate = $hastaDate->copy()->startOfDay();
            $hastaDate = $tmp->copy()->endOfDay();
            $desde = $desdeDate->format('Y-m-d');
            $hasta = $hastaDate->format('Y-m-d');
        }

        if (count($unidadIds) > 0) {
            $unidadesFiltro = Unidad::where('estado', 'A')
                ->whereIn('_id', $unidadIds)
                ->pluck('imei')
                ->filter(function ($v) {
                    return !empty($v);
                })
                ->values()
                ->toArray();

            if (count($unidadesFiltro) > 0) {
                $query->whereIn('imei', $unidadesFiltro);
            } else {
                $query->where('imei', '__unidad_invalida__');
            }
        } elseif ($cooperativaId !== '') {
            $imeis = Unidad::orderBy('descripcion', 'asc')
                ->where('estado', 'A')
                ->where('cooperativa_id', $cooperativaId)
                ->pluck('imei')
                ->filter(function ($v) {
                    return !empty($v);
                })
                ->values()
                ->toArray();

            if (count($imeis) > 0) {
                $query->whereIn('imei', $imeis);
            } else {
                $query->where('imei', '__sin_unidades__');
            }
        }

        $query->where('fecha', '>=', $desdeDate)->where('fecha', '<=', $hastaDate);

        $perPage = (int) $request->query('per_page', 50);
        if ($perPage < 1) {
            $perPage = 50;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $items = $query->paginate($perPage);
        $items->appends($request->query());

        $imeisPagina = $items->pluck('imei')
            ->filter(function ($v) {
                return !empty($v);
            })
            ->values()
            ->toArray();

        $unidadesPorImei = array();
        if (count($imeisPagina) > 0) {
            $unidadesPagina = Unidad::whereIn('imei', $imeisPagina)->get();

            foreach ($unidadesPagina as $u) {
                $k = trim((string) $u->imei);
                if ($k === '' || isset($unidadesPorImei[$k])) {
                    continue;
                }
                $unidadesPorImei[$k] = trim((string) $u->descripcion) . ' - ' . trim((string) $u->placa);
            }
        }

        $baseImagenUrl = url('/api/v2/photo-unidades');
        $direccionPorCoordenadas = array();
        $data = array();
        foreach ($items as $row) {
            $id = (string) $row->_id;
            $imeiKey = trim((string) $row->imei);
            $latitud = isset($row->latitud) ? $row->latitud : null;
            $longitud = isset($row->longitud) ? $row->longitud : null;
            $direccion = null;
            if ($latitud !== null && $longitud !== null && $latitud !== '' && $longitud !== ''
                && is_numeric($latitud) && is_numeric($longitud)) {
                $latF = (float) $latitud;
                $lngF = (float) $longitud;
                $coordCacheKey = sprintf('%.5f,%.5f', $latF, $lngF);
                if (!array_key_exists($coordCacheKey, $direccionPorCoordenadas)) {
                    $direccionPorCoordenadas[$coordCacheKey] = $this->locationIqReverseDisplayName($latF, $lngF);
                }
                $direccion = $direccionPorCoordenadas[$coordCacheKey];
            }
            $data[] = array(
                'id' => $id,
                'imei' => $row->imei,
                'tipo' => $row->tipo ?? null,
                'tipo_evento' => $row->tipo_evento ?? null,
                'fecha_gps' => $this->fechaParseada($row->fecha_gps),
                'latitud' => $latitud,
                'longitud' => $longitud,
                'direccion' => $direccion,
                'imagen' => $row->imagen ?? null,
                'fecha' => $row->fecha ? $row->fecha->format('c') : null,
                'js' => $row->js ?? null,
                'unidad_etiqueta' => isset($unidadesPorImei[$imeiKey]) ? $unidadesPorImei[$imeiKey] : null,
                'imagen_url' => $baseImagenUrl . '/' . rawurlencode($id) . '/imagen',
            );
        }

        return response()->json(array(
            'error' => false,
            'api_version' => 'v2',
            'data' => $data,
            'meta' => array(
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ),
            'filters' => array(
                'desde' => $desde,
                'hasta' => $hasta,
                'cooperativa_id' => $cooperativaId,
                'unidad_ids' => $unidadIds,
            ),
        ), 200);
    }

    /**
     * Sirve el archivo de imagen (ruta segura bajo storage/app/images).
     */
    public function showImage($id)
    {
        $item = PhotoUnidad::findOrFail($id);

        $relativePath = ltrim((string) $item->imagen, '/');
        if ($relativePath === '') {
            abort(404);
        }

        $basePath = storage_path('app/images');
        $fullPath = storage_path('app/' . $relativePath);

        $realBase = realpath($basePath);
        $realFile = realpath($fullPath);
        if ($realBase === false || $realFile === false) {
            abort(404);
        }

        if (strpos($realFile, $realBase) !== 0 || !is_file($realFile)) {
            abort(404);
        }

        return response()->file($realFile);
    }

    /**
     * Igual que HistoricoController: toDateTime() y restar 10 horas a fecha_gps.
     *
     * @param mixed $fecha_gps
     * @return string|null
     */
    private function fechaParseada($fecha_gps)
    {
        if ($fecha_gps === null || !is_object($fecha_gps) || !method_exists($fecha_gps, 'toDateTime')) {
            return null;
        }

        $f_gps = $fecha_gps->toDateTime();
        date_sub($f_gps, date_interval_create_from_date_string('5 hours'));

        return $f_gps->format('c');
    }

    /**
     * Reverse geocoding LocationIQ (mismo criterio que el parseador: LOCATIONIQ_API_KEY, LOCATIONIQ_REVERSE_BASE).
     *
     * @param float $lat
     * @param float $lng
     * @return string|null
     */
    private function locationIqReverseDisplayName($lat, $lng)
    {
        $apiKey = trim((string) env('LOCATIONIQ_API_KEY', ''));
        if ($apiKey === '') {
            return null;
        }
        if (!is_finite($lat) || !is_finite($lng) || abs($lat) > 90 || abs($lng) > 180) {
            return null;
        }

        $base = rtrim((string) env('LOCATIONIQ_REVERSE_BASE', 'https://us1.locationiq.com/v1/reverse'), '/');
        $timeoutMs = (int) env('LOCATIONIQ_REVERSE_TIMEOUT_MS', 8000);
        if ($timeoutMs < 500) {
            $timeoutMs = 500;
        }
        $timeoutSec = $timeoutMs / 1000.0;

        try {
            $client = new Client(array(
                'timeout' => $timeoutSec,
                'connect_timeout' => 3.0,
            ));
            $response = $client->get($base, array(
                'query' => array(
                    'key' => $apiKey,
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'json',
                    'accept-language' => 'es',
                ),
                'http_errors' => false,
            ));
            if ($response->getStatusCode() !== 200) {
                return null;
            }
            $json = json_decode((string) $response->getBody(), true);
            if (!is_array($json)) {
                return null;
            }
            if (isset($json['error'])) {
                return null;
            }
            if (empty($json['display_name'])) {
                return null;
            }

            return trim((string) $json['display_name']);
        } catch (\Exception $e) {
            \Log::debug('PhotoUnidadApi LocationIQ reverse: '.$e->getMessage());

            return null;
        }
    }
}
