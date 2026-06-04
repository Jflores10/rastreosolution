<?php

namespace App\Http\Controllers;

use App\Cooperativa;
use App\PhotoUnidad;
use App\Services\LocationIqReverseGeocodeService;
use App\Unidad;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PhotoUnidadController extends Controller
{
    private function aplicarPermisosUnidades($query, $user, $cooperativaId = '')
    {
        $tipoUsuario = isset($user->tipo_usuario) ? (string) $user->tipo_usuario->valor : '';

        if ($tipoUsuario !== '1') {
            $query->where('cooperativa_id', $user->cooperativa_id);
            if (($tipoUsuario === '4' || $tipoUsuario === '5') && !empty($user->unidades_pertenecientes) && is_array($user->unidades_pertenecientes)) {
                $query->whereIn('_id', $user->unidades_pertenecientes);
            }
        } elseif ($cooperativaId !== '' && $cooperativaId !== 'none') {
            $query->where('cooperativa_id', $cooperativaId);
        }

        return $query;
    }

    /**
     * Dirección por foto (id => texto) vía LocationIQ + caché geocache.
     *
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection $items
     * @param LocationIqReverseGeocodeService $geoService
     * @return array
     */
    private function ubicacionesPorFotos($items, LocationIqReverseGeocodeService $geoService)
    {
        $direccionPorCoordenadas = array();
        $ubicacionesPorId = array();

        foreach ($items as $row) {
            $id = (string) $row->getKey();
            $latitud = isset($row->latitud) ? $row->latitud : null;
            $longitud = isset($row->longitud) ? $row->longitud : null;
            $direccion = null;

            if ($latitud !== null && $longitud !== null && $latitud !== '' && $longitud !== ''
                && is_numeric($latitud) && is_numeric($longitud)) {
                $latF = (float) $latitud;
                $lngF = (float) $longitud;
                $rounded = $geoService->roundCoordinates($latF, $lngF);
                if ($rounded !== null) {
                    $coordCacheKey = $rounded[0] . ',' . $rounded[1];
                    if (!array_key_exists($coordCacheKey, $direccionPorCoordenadas)) {
                        $direccionPorCoordenadas[$coordCacheKey] = $geoService->reverseDisplayName($latF, $lngF);
                    }
                    $direccion = $direccionPorCoordenadas[$coordCacheKey];
                }
            }

            $ubicacionesPorId[$id] = $direccion;
        }

        return $ubicacionesPorId;
    }

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $u = $request->user();
            if (!$u || !isset($u->tipo_usuario) || !in_array($u->tipo_usuario->valor, array('1', '2', '3', '4', '5'), true)) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $user = $request->user();
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

        $cooperativas = Cooperativa::orderBy('descripcion', 'asc')->where('estado', 'A')->permitida()->get();
        $unidadesQuery = Unidad::orderBy('descripcion', 'asc')->where('estado', 'A');
        $unidades = $this->aplicarPermisosUnidades($unidadesQuery, $user)->get();

        if (count($unidadIds) > 0) {
            $unidadesFiltroQuery = Unidad::where('estado', 'A');
            $unidadesFiltro = $this->aplicarPermisosUnidades($unidadesFiltroQuery, $user)
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
                // Fuerza vacío si ninguna unidad es válida para el usuario actual.
                $query->where('imei', '__unidad_invalida__');
            }
        } elseif ($cooperativaId !== '') {
            $imeisQuery = Unidad::orderBy('descripcion', 'asc')
                ->where('estado', 'A');
            $imeis = $this->aplicarPermisosUnidades($imeisQuery, $user, $cooperativaId)
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

        $tipoUsuario = isset($user->tipo_usuario) ? (string) $user->tipo_usuario->valor : '';
        $esDistribuidor = $tipoUsuario === '1';
        if (!$esDistribuidor) {
            $query->where('marcada', true);
        }

        $items = $query->paginate(50);
        $items->appends($request->query());

        $imeisPagina = $items->pluck('imei')
            ->filter(function ($v) {
                return !empty($v);
            })
            ->values()
            ->toArray();

        $unidadesPorImei = array();
        if (count($imeisPagina) > 0) {
            $unidadesPaginaQuery = Unidad::query();
            $unidadesPagina = $this->aplicarPermisosUnidades($unidadesPaginaQuery, $user)
                ->whereIn('imei', $imeisPagina)
                ->get();

            foreach ($unidadesPagina as $u) {
                $k = trim((string) $u->imei);
                if ($k === '' || isset($unidadesPorImei[$k])) {
                    continue;
                }
                $unidadesPorImei[$k] = trim((string) $u->descripcion) . ' - ' . trim((string) $u->placa);
            }
        }

        $geoService = app(LocationIqReverseGeocodeService::class);
        $ubicacionesPorId = $this->ubicacionesPorFotos($items, $geoService);

        return view('panel.fotos.index', array(
            'items' => $items,
            'cooperativas' => $cooperativas,
            'unidades' => $unidades,
            'cooperativa_id' => $cooperativaId,
            'unidad_ids' => $unidadIds,
            'desde' => $desde,
            'hasta' => $hasta,
            'unidades_por_imei' => $unidadesPorImei,
            'ubicaciones_por_id' => $ubicacionesPorId,
            'es_distribuidor' => $esDistribuidor,
        ));
    }

    public function marcar(Request $request, $id)
    {
        $user = $request->user();
        $tipoUsuario = isset($user->tipo_usuario) ? (string) $user->tipo_usuario->valor : '';
        if ($tipoUsuario !== '1') {
            return response()->json(array('success' => false, 'message' => 'No autorizado'), 403);
        }

        $marcadaInput = $request->input('marcada');
        $marcar = null;
        if (in_array($marcadaInput, array(1, '1', true, 'true', 'on'), true)) {
            $marcar = true;
        } elseif (in_array($marcadaInput, array(0, '0', false, 'false', 'off'), true)) {
            $marcar = false;
        }
        if ($marcar === null) {
            return response()->json(array('success' => false, 'message' => 'Valor de marcada no válido'), 422);
        }

        $item = PhotoUnidad::findOrFail($id);
        $imei = trim((string) $item->imei);
        $unidad = ($imei !== '') ? Unidad::where('imei', $imei)->first() : null;

        if ($marcar) {
            if (!empty($item->marcada)) {
                return response()->json(array(
                    'success' => true,
                    'marcada' => true,
                    'contador_img' => $unidad ? PhotoUnidad::contadorMarcadasHoyPorImei($imei) : 0,
                ));
            }

            $item->marcada = true;
            $item->fecha_marca = Carbon::now();
            $item->save();
        } else {
            if (empty($item->marcada)) {
                return response()->json(array(
                    'success' => true,
                    'marcada' => false,
                    'contador_img' => $unidad ? PhotoUnidad::contadorMarcadasHoyPorImei($imei) : 0,
                ));
            }

            $item->marcada = false;
            $item->fecha_marca = null;
            $item->save();
        }

        $contadorImg = 0;
        if ($unidad) {
            $contadorImg = PhotoUnidad::contadorMarcadasHoyPorImei($imei);
            $unidad->contador_img = $contadorImg;
            $unidad->save();
        }

        return response()->json(array(
            'success' => true,
            'marcada' => (bool) $item->marcada,
            'contador_img' => $contadorImg,
        ));
    }

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
