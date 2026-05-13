<?php

namespace App\Http\Controllers;

use App\PhotoUnidad;
use App\Unidad;
use Carbon\Carbon;
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
        $data = array();
        foreach ($items as $row) {
            $id = (string) $row->_id;
            $imeiKey = trim((string) $row->imei);
            $data[] = array(
                'id' => $id,
                'imei' => $row->imei,
                'tipo' => $row->tipo ?? null,
                'tipo_evento' => $row->tipo_evento ?? null,
                'fecha_gps' => $row->fecha_gps ? $row->fecha_gps->format('c') : null,
                'latitud' => $row->latitud ?? null,
                'longitud' => $row->longitud ?? null,
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
}
