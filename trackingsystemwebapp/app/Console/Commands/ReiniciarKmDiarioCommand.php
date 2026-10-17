<?php

namespace App\Console\Commands;

use App\KmDiarioHistorial;
use App\Unidad;
use Illuminate\Console\Command;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDateTime;

/**
 * Corte diario (programado a las 23:59 America/Guayaquil). Para cada unidad activa:
 *
 * - Km diario: guarda el día que termina en km_diario_historial y registra el inicio
 *   del día siguiente: odometro_inicio_dia = último odómetro recibido (campo 19 de la
 *   GTFRI), km_diario = 0, fecha_km_diario = día que empieza. `mileage` no se usa.
 * - Encendido / apagado acumulado: cierra el tramo en curso hasta las 23:59, guarda
 *   los totales del día (seg_encendido / seg_apagado en el mismo historial) y deja
 *   los contadores en 0, con el tramo actual empezando a las 23:59.
 *
 * Todo queda en BD, así que sobrevive a reinicios del parseador.
 */
class ReiniciarKmDiarioCommand extends Command
{
    const LOTE = 500;

    protected $signature = 'ts:reiniciar-km-diario {--fecha= : Día que empieza (Y-m-d). Por defecto, el siguiente al corte de las 23:59} {--imei= : Solo la unidad con este IMEI}';

    protected $description = 'Corte de las 23:59: km diario y tiempo encendido/apagado acumulado del día';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            // A las 23:59 (+1 h) ya es el día siguiente; también si el cron corre con retraso.
            $hoy = $this->option('fecha') ?: \Carbon\Carbon::now(Unidad::ZONA_KM_DIARIO)->addHour()->toDateString();
            // Instante del corte: 23:59:00 local del día anterior al que empieza.
            $corte = \Carbon\Carbon::parse($hoy . ' 00:00:00', Unidad::ZONA_KM_DIARIO)->subMinute();
            $procesadas = 0;
            $cerradas = 0;
            $filtro = [
                'estado' => 'A',
                '$or' => [
                    ['fecha_km_diario' => ['$lt' => $hoy]],
                    ['fecha_km_diario' => null],
                    ['fecha_ignicion_dia' => ['$lt' => $hoy]],
                    ['fecha_ignicion_dia' => null],
                ],
            ];
            if ($this->option('imei'))
                $filtro['imei'] = (string) $this->option('imei');

            // Cada pasada actualiza las fechas al día que empieza, así que las procesadas dejan
            // de cumplir el filtro (no se usa paginación por offset, que se saltaría
            // documentos al cambiar el campo filtrado). El tope evita un bucle infinito.
            for ($pasada = 0; $pasada < 1000; $pasada++) {
                $unidades = Unidad::raw(function ($collection) use ($filtro) {
                    return $collection->find(
                        $filtro,
                        [
                            'projection' => [
                                'imei' => 1, 'odometro' => 1, 'odometro_inicio_dia' => 1, 'fecha_km_diario' => 1,
                                'ignicionf' => 1, 'seg_encendido_dia' => 1, 'seg_apagado_dia' => 1,
                                'ignicion_tramo_desde' => 1, 'fecha_ignicion_dia' => 1,
                            ],
                            'limit' => self::LOTE,
                        ]
                    )->toArray();
                });

                if (count($unidades) === 0)
                    break;

                foreach ($unidades as $u) {
                    $id = $u['_id'] instanceof ObjectID ? $u['_id'] : new ObjectID((string) $u['_id']);
                    $km = $this->cerrarKmDiario($u, $id, $hoy);
                    $ign = $this->cerrarIgnicionDia($u, $id, $hoy, $corte);
                    if ($km || $ign)
                        $cerradas++;
                    $procesadas++;
                }
            }

            $this->info('Unidades reiniciadas: ' . $procesadas . ', días guardados en historial: ' . $cerradas . '.');
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());

            return 1;
        }

        return 0;
    }

    /**
     * Km diario: guarda el día que termina (si lo hay) y deja la unidad en el día que
     * empieza con 0 km. Devuelve true si se guardó un día en el historial.
     */
    private function cerrarKmDiario($u, $id, $hoy)
    {
        $fechaAnterior = isset($u['fecha_km_diario']) ? (string) $u['fecha_km_diario'] : null;
        if ($fechaAnterior !== null && $fechaAnterior >= $hoy)
            return false;
        $inicio = isset($u['odometro_inicio_dia']) && is_numeric($u['odometro_inicio_dia']) ? (float) $u['odometro_inicio_dia'] : null;
        $odometro = isset($u['odometro']) && is_numeric($u['odometro']) ? (float) $u['odometro'] : null;
        $odometroValido = $odometro !== null && $odometro > 0;

        // Solo se toca la unidad si el parseador no la reinició mientras tanto.
        $modificada = $this->actualizarSiSigueIgual(
            ['_id' => $id, 'fecha_km_diario' => $fechaAnterior],
            [
                'odometro_inicio_dia' => $odometroValido ? round($odometro, 2) : null,
                'km_diario' => 0,
                'fecha_km_diario' => $hoy,
            ]
        );
        if (!$modificada || $fechaAnterior === null || $inicio === null)
            return false;

        $fin = $odometroValido ? $odometro : $inicio;
        $this->guardarHistorial($id, $u, $fechaAnterior, [
            'odometro_inicio' => round($inicio, 2),
            'odometro_fin' => round($fin, 2),
            'km_recorridos' => round(max(0, $fin - $inicio), 2),
        ]);

        return true;
    }

    /**
     * Encendido / apagado: suma al contador del estado actual lo transcurrido hasta el
     * corte, guarda los totales del día y reinicia los contadores en 0 con el tramo en
     * curso empezando en el corte. Devuelve true si se guardó un día en el historial.
     */
    private function cerrarIgnicionDia($u, $id, $hoy, \Carbon\Carbon $corte)
    {
        $fechaAnterior = isset($u['fecha_ignicion_dia']) ? (string) $u['fecha_ignicion_dia'] : null;
        if ($fechaAnterior !== null && $fechaAnterior >= $hoy)
            return false;

        $estado = isset($u['ignicionf']) && in_array($u['ignicionf'], ['on', 'off'], true) ? $u['ignicionf'] : null;
        $segOn = isset($u['seg_encendido_dia']) && is_numeric($u['seg_encendido_dia']) ? (float) $u['seg_encendido_dia'] : 0.0;
        $segOff = isset($u['seg_apagado_dia']) && is_numeric($u['seg_apagado_dia']) ? (float) $u['seg_apagado_dia'] : 0.0;
        $desde = isset($u['ignicion_tramo_desde']) && $u['ignicion_tramo_desde'] instanceof UTCDateTime
            ? $u['ignicion_tramo_desde']->toDateTime()->getTimestamp()
            : null;
        // Nunca en el futuro (p. ej. una ejecución manual con --fecha antes de las 23:59).
        $corteTs = min($corte->getTimestamp(), time());

        // Tramo en curso hasta el corte (pertenece al día que termina).
        if ($fechaAnterior !== null && $estado !== null && $desde !== null && $corteTs > $desde) {
            if ($estado === 'on')
                $segOn += $corteTs - $desde;
            else
                $segOff += $corteTs - $desde;
        }

        // Primera vez (sin contadores): se empieza a acumular desde ahora, sin suponer
        // que el estado actual viene desde el corte.
        $nuevoDesde = $fechaAnterior !== null ? $corteTs : max($corteTs, time());

        $modificada = $this->actualizarSiSigueIgual(
            ['_id' => $id, 'fecha_ignicion_dia' => $fechaAnterior],
            [
                'seg_encendido_dia' => 0,
                'seg_apagado_dia' => 0,
                'ignicion_tramo_desde' => new UTCDateTime($nuevoDesde * 1000),
                'fecha_ignicion_dia' => $hoy,
            ]
        );
        if (!$modificada || $fechaAnterior === null)
            return false;

        $this->guardarHistorial($id, $u, $fechaAnterior, [
            'seg_encendido' => (int) round($segOn),
            'seg_apagado' => (int) round($segOff),
        ]);

        return true;
    }

    /** updateOne condicionado: false si el documento cambió (p. ej. lo reinició el parseador). */
    private function actualizarSiSigueIgual(array $filtro, array $set)
    {
        $resultado = Unidad::raw(function ($collection) use ($filtro, $set) {
            return $collection->updateOne($filtro, ['$set' => $set]);
        });

        return is_object($resultado) && method_exists($resultado, 'getModifiedCount')
            ? $resultado->getModifiedCount() > 0
            : true;
    }

    /** Resumen del día en km_diario_historial (upsert por unidad y fecha). */
    private function guardarHistorial($id, $u, $fecha, array $campos)
    {
        $ahora = new UTCDateTime();
        KmDiarioHistorial::raw(function ($collection) use ($id, $u, $fecha, $campos, $ahora) {
            return $collection->updateOne(
                ['unidad_id' => $id, 'fecha' => $fecha],
                [
                    '$set' => array_merge($campos, [
                        'imei' => isset($u['imei']) ? $u['imei'] : null,
                        'updated_at' => $ahora,
                    ]),
                    '$setOnInsert' => ['created_at' => $ahora],
                ],
                ['upsert' => true]
            );
        });
    }
}
