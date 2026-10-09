<?php

namespace App\Console\Commands;

use App\KmDiarioHistorial;
use App\Unidad;
use Illuminate\Console\Command;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDateTime;

/**
 * Cierra el km diario de las unidades que no han enviado tramas desde medianoche.
 *
 * El parseador reinicia el km diario con la primera trama GTFRI del nuevo día; una
 * unidad apagada toda la noche no envía tramas y seguiría mostrando el km de ayer.
 * Este comando guarda ese día en km_diario_historial y deja el día de hoy en 0.
 */
class ReiniciarKmDiarioCommand extends Command
{
    const LOTE = 500;

    protected $signature = 'ts:reiniciar-km-diario';

    protected $description = 'Cierra el km diario del día anterior y reinicia km_diario en las unidades activas';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            $hoy = Unidad::fechaHoyKmDiario();
            $procesadas = 0;
            $cerradas = 0;

            // Cada pasada actualiza fecha_km_diario a hoy, así que las procesadas dejan
            // de cumplir el filtro (no se usa paginación por offset, que se saltaría
            // documentos al cambiar el campo filtrado). El tope evita un bucle infinito.
            for ($pasada = 0; $pasada < 1000; $pasada++) {
                $unidades = Unidad::raw(function ($collection) use ($hoy) {
                    return $collection->find(
                        [
                            'estado' => 'A',
                            '$or' => [
                                ['fecha_km_diario' => ['$lt' => $hoy]],
                                ['fecha_km_diario' => null],
                            ],
                        ],
                        [
                            'projection' => ['imei' => 1, 'mileage' => 1, 'mileage_inicio_dia' => 1, 'fecha_km_diario' => 1],
                            'limit' => self::LOTE,
                        ]
                    )->toArray();
                });

                if (count($unidades) === 0)
                    break;

                foreach ($unidades as $u) {
                    if ($this->cerrarDia($u, $hoy))
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
     * Guarda el día anterior (si lo hay) y deja la unidad en el día de hoy con 0 km.
     * Devuelve true si se guardó un día en el historial.
     */
    private function cerrarDia($u, $hoy)
    {
        $id = $u['_id'] instanceof ObjectID ? $u['_id'] : new ObjectID((string) $u['_id']);
        $fechaAnterior = isset($u['fecha_km_diario']) ? (string) $u['fecha_km_diario'] : null;
        $inicio = isset($u['mileage_inicio_dia']) && is_numeric($u['mileage_inicio_dia']) ? (float) $u['mileage_inicio_dia'] : null;
        $mileage = isset($u['mileage']) && is_numeric($u['mileage']) ? (float) $u['mileage'] : null;
        $mileageValido = $mileage !== null && $mileage > 0;

        // Solo se toca la unidad si el parseador no la reinició mientras tanto.
        $filtro = ['_id' => $id, 'fecha_km_diario' => $fechaAnterior];
        $resultado = Unidad::raw(function ($collection) use ($filtro, $hoy, $mileage, $mileageValido) {
            return $collection->updateOne($filtro, [
                '$set' => [
                    'mileage_inicio_dia' => $mileageValido ? round($mileage, 2) : null,
                    'km_diario' => 0,
                    'fecha_km_diario' => $hoy,
                ],
            ]);
        });

        $modificada = is_object($resultado) && method_exists($resultado, 'getModifiedCount')
            ? $resultado->getModifiedCount() > 0
            : true;
        if (!$modificada || $fechaAnterior === null || $inicio === null)
            return false;

        $fin = $mileageValido ? $mileage : $inicio;
        $ahora = new UTCDateTime();
        // Mismo documento que escribe el parseador (upsert por unidad y fecha).
        KmDiarioHistorial::raw(function ($collection) use ($id, $u, $fechaAnterior, $inicio, $fin, $ahora) {
            return $collection->updateOne(
                ['unidad_id' => $id, 'fecha' => $fechaAnterior],
                [
                    '$set' => [
                        'imei' => isset($u['imei']) ? $u['imei'] : null,
                        'mileage_inicio' => round($inicio, 2),
                        'mileage_fin' => round($fin, 2),
                        'km_recorridos' => round(max(0, $fin - $inicio), 2),
                        'updated_at' => $ahora,
                    ],
                    '$setOnInsert' => ['created_at' => $ahora],
                ],
                ['upsert' => true]
            );
        });

        return true;
    }
}
