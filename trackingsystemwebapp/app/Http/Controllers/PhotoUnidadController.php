<?php

namespace App\Http\Controllers;

use App\Cooperativa;
use App\PhotoUnidad;
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

        return view('panel.fotos.index', array(
            'items' => $items,
            'cooperativas' => $cooperativas,
            'unidades' => $unidades,
            'cooperativa_id' => $cooperativaId,
            'unidad_ids' => $unidadIds,
            'desde' => $desde,
            'hasta' => $hasta,
            'unidades_por_imei' => $unidadesPorImei,
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
