<?php

namespace App\Http\Controllers;


use App\PuntoControl;
use App\TipoUsuario;
use App\Despacho;
use Illuminate\Http\Request;
use App\Cooperativa;
use App\Recorrido;
use Carbon\Carbon;
use MongoDB\BSON\UTCDateTime;
use App\Unidad;
use MongoDB\BSON\ObjectID;
use Auth;
use Validator;
use DateTime;
use App\Ruta;
use App\PuntoVirtual;
use GuzzleHttp\Client;
use App\Bitacora;
use Illuminate\Support\Facades\Cache;
use App\User;
use Excel;
use App\Helper\FunctionsHelper;


class HistoricoApiController extends Controller
{
    
    public function getUnidadesMeta(Request $request)
    {
        $this->validate($request, [
            'unidad_ids' => 'required'
        ]);

        $ids = $request->input('unidad_ids');
        if (!is_array($ids)) $ids = [$ids];
        $result = [];
        $desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
        $hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));

       
        // First, try to fill from cache
        $toFetch = [];
        foreach ($ids as $uid) {
            $cached = Cache::get('unidades_meta_' . $uid);
            if ($cached) {
                $result[$uid] = $cached;
            } else {
                $toFetch[] = $uid;
            }
        }

        if (count($toFetch) === 0) {
            return response()->json($result);
        }

        try {
            // Batch fetch ignicionf from unidades (single query, only the field we need)
            $unidadesIgnicion = Unidad::whereIn('_id', $toFetch)
                ->where('estado', 'A')
                ->get(['_id', 'ignicionf', 'tiempo_power', 'tiempo_power_update']);
            $ignicionByUnidad = [];
            $tiempoPowerByUnidad = [];
            foreach ($unidadesIgnicion as $u) {
                $ignicionByUnidad[(string)$u->_id] = $u->ignicionf ?? null;
                $tiempoPowerByUnidad[(string)$u->_id] = [
                    'tiempo_power'        => $u->tiempo_power ?? 0,
                    'tiempo_power_update' => $u->tiempo_power_update ?? null,
                ];
            }

            // Batch fetch despachos for the given unidades within today range
            $despachos = Despacho::orderBy('fecha', 'asc')
                ->where('estado', 'P')
                ->whereIn('unidad_id', $toFetch)
                ->where('fecha', '>=', $desde)
                ->where('fecha', '<=', $hasta)
                ->get();

            // For bitacoras, apply visibility rules similar to BitacoraController
            $user = Auth::user();
            $bitacorasQuery = Bitacora::orderBy('fechaInicio', 'desc')
                ->whereIn('unidad_id', $toFetch)
                ->where('estado', 'P');

            if ($user->tipo_usuario->valor != 1) {
                $usuarios_distribuidores = User::where('tipo_usuario_id', '5827714b7b10202ff4485891')->get();
                $usr_distribuidores = [];
                foreach ($usuarios_distribuidores as $usr) array_push($usr_distribuidores, $usr->_id);

                $bitacorasQuery->where(function ($q) use ($usr_distribuidores) {
                    $q->whereNotIn('creador_id', $usr_distribuidores)
                      ->orWhere('compartido', 'S');
                });
            }

            $bitacoras = $bitacorasQuery->get();

            // Map first despacho per unidad (ordered asc, so first is earliest)
            $despByUnidad = [];
            foreach ($despachos as $d) {
                $uid = (string)$d->unidad_id;
                if (!isset($despByUnidad[$uid])) $despByUnidad[$uid] = $d;
            }

            // Map first (most recent) bitacora per unidad (ordered desc)
            $bitByUnidad = [];
            foreach ($bitacoras as $b) {
                $uid = (string)$b->unidad_id;
                if (!isset($bitByUnidad[$uid])) $bitByUnidad[$uid] = $b;
            }

            foreach ($toFetch as $uid) {
                $ruta_descr = '';
                $ruta_fecha = '';
                $ruta_conductor = '';
                $ruta_hora_final = '';
                $tipo_bitacora = '';

                if (isset($despByUnidad[$uid])) {
                    $r = $despByUnidad[$uid];
                    $ruta_descr = $r->ruta->descripcion ?? '';
                    // ruta fecha (hora despacho ajustada)
                    $rf = $r->fecha;
                    date_add($rf, date_interval_create_from_date_string('5 hours'));
                    $ruta_fecha = $rf->format('H:i');
                    $ruta_conductor = $r->conductor->nombre ?? '';

                    // calcular hora final estimada sumando los tiempos de llegada de los puntos de control
                    $tiempo_final = 0;
                    if (isset($r->ruta) && isset($r->ruta->puntos_control) && is_array($r->ruta->puntos_control)) {
                        foreach ($r->ruta->puntos_control as $punto) {
                            if (isset($punto['tiempo_llegada'])) $tiempo_final += (int)$punto['tiempo_llegada'];
                        }
                    }

                    try {
                        $rh = Carbon::parse($r->fecha);
                        $rh->addHours(5);
                        if ($tiempo_final > 0) $rh->addMinutes($tiempo_final);
                        $ruta_hora_final = $rh->format('H:i');
                    } catch (\Exception $ex) {
                        $ruta_hora_final = '';
                    }
                }

                if (isset($bitByUnidad[$uid])) {
                    $tipo_bitacora = $bitByUnidad[$uid]->tipo_bitacora ?? '';
                }

                // Siempre enviar ruta_* (vacíos si no hay despacho) para que el front borre la ruta al finalizar
                $meta = [];
                $meta['ruta_actual'] = ($ruta_descr !== null && $ruta_descr !== '') ? $ruta_descr : '';
                $meta['ruta_fecha'] = ($ruta_fecha !== null && $ruta_fecha !== '') ? $ruta_fecha : '';
                $meta['ruta_conductor'] = ($ruta_conductor !== null && $ruta_conductor !== '') ? $ruta_conductor : '';
                $meta['ruta_hora_fin'] = ($ruta_hora_final !== null && $ruta_hora_final !== '') ? $ruta_hora_final : '';
                if ($tipo_bitacora !== null && $tipo_bitacora !== '') $meta['tipo_bitacora'] = $tipo_bitacora;
                // ignicionf: always include when present (on/off) so the front-end can show the icon on initial load
                $ignf = $ignicionByUnidad[$uid] ?? null;
                if ($ignf === 'on' || $ignf === 'off') $meta['ignicionf'] = $ignf;

                // tiempo_power: incluir siempre para que el front pueda evaluar si el bolt debe parpadear
                $tpData = $tiempoPowerByUnidad[$uid] ?? ['tiempo_power' => 0, 'tiempo_power_update' => null];
                $tp  = (float)($tpData['tiempo_power'] ?? 0);
                $tpu = $tpData['tiempo_power_update'];

                // Serializar tiempo_power_update a ISO 8601 legible por JS
                $tpu_iso = null;
                if ($tpu !== null) {
                    try {
                        $tpu_iso = $tpu->toDateTime()->format('Y-m-d\TH:i:s\Z');
                    } catch (\Exception $e) { $tpu_iso = null; }
                }

                // Calcular horas restantes y exponer bolt_activo para que el JS no tenga que calcular
                $bolt_activo = false;
                if ($tp > 0 && $tpu_iso !== null) {
                    $now = new DateTime('now', new \DateTimeZone('UTC'));
                    $fechaUpdate = new DateTime($tpu_iso, new \DateTimeZone('UTC'));
                    $horasTranscurridas = ($now->getTimestamp() - $fechaUpdate->getTimestamp()) / 3600;
                    $bolt_activo = ($tp - $horasTranscurridas) > 0;
                }

                $meta['tiempo_power']        = $tp;
                $meta['tiempo_power_update'] = $tpu_iso;
                $meta['bolt_activo']         = $bolt_activo;

                // cache short-term (even an empty object to avoid hot loops)
                try {
                    Cache::put('unidades_meta_' . $uid, $meta, Carbon::now()->addSeconds(15));
                } catch (\Exception $e) {
                    // ignorar errores de cache
                }

                // return object ({} in JSON) when there are no keys rather than fields with empty strings
                $result[$uid] = (object)$meta;
            }
        } catch (Exception $e) {
            // on error, ensure all toFetch keys exist
            foreach ($toFetch as $uid) {
                $result[$uid] = (object)[];
            }
        }

        return response()->json($result);
    }
   

}