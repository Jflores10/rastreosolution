<?php

namespace App\Console\Commands;

use App\Cooperativa;
use App\Despacho;
use App\Http\Controllers\DespachoController;
use App\TipoUsuario;
use App\Unidad;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MongoDB\BSON\ObjectID;
use App\Ruta;

class FinalizarDespachosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ts:finalizar-despachos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finaliza los despachos pendientes de las unidades de transporte al cumplirse el tiempo esperado de finalizacion de ruta';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {

            $hoy_desde = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
            $hoy_hasta = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
            date_sub($hoy_desde, date_interval_create_from_date_string('5 hours'));
            date_sub($hoy_hasta, date_interval_create_from_date_string('5 hours'));

            $cooperativas = Cooperativa::where('despachos_job', 'S')
                ->where('estado', 'A')
                ->get();

            if ($cooperativas->isEmpty()) {
                return;
            }


            $distributorType = TipoUsuario::where('valor', '1')->firstOrFail();
            $user = User::where('tipo_usuario_id', $distributorType->_id)->firstOrFail();
            Auth::onceUsingId($user->_id);

            $ctrl = app()->make(\App\Http\Controllers\DespachoController::class);
            $fakeRequest = \Illuminate\Http\Request::create('/', 'GET', []);

            foreach ($cooperativas as $cooperativa) {

                $unidades = Unidad::where('cooperativa_id', $cooperativa->_id)
                    ->where('estado', 'A')
                    ->get();

                if ($unidades->isEmpty()) {
                    continue;
                }

                $despachos = Despacho::with('ruta')
                    ->whereIn('unidad_id', $unidades->pluck('_id'))
                    ->where('estado', 'P')
                    ->where('fecha', '>=', $hoy_desde)
                    ->where('fecha', '<=', $hoy_hasta)
                    ->get();

                if ($despachos->isEmpty()) {
                    continue;
                }

                // Varios P del día por unidad: no tocar despachos futuros;
                // solo los ya iniciados cuyo tiempo_esperado ya venció.
                $porUnidad = $despachos->groupBy(function ($d) {
                    return (string) $d->unidad_id;
                });

                foreach ($porUnidad as $despachosUnidad) {
                    $ahora = Carbon::now('America/Guayaquil');
                    $ordenados = $despachosUnidad->sortBy(function ($d) {
                        return $d->fecha ? $d->fecha->getTimestamp() : 0;
                    })->values();

                    foreach ($ordenados as $despacho) {
                        try {
                            if (empty($despacho->fecha)) {
                                continue;
                            }

                            $inicio = Carbon::instance($despacho->fecha->toDateTime())
                                ->addHours(5)
                                ->setTimezone('America/Guayaquil');

                            // Aún no empieza: los siguientes también son futuros
                            if ($ahora->lt($inicio)) {
                                break;
                            }

                            $puntos = $despacho->puntos_control ?? [];
                            if (empty($puntos)) {
                                continue;
                            }

                            $ultimo = end($puntos);
                            if (empty($ultimo['tiempo_esperado'])) {
                                continue;
                            }

                            $tiempoEsperado = Carbon::instance($ultimo['tiempo_esperado']->toDateTime())
                                ->addHours(5)
                                ->setTimezone('America/Guayaquil');

                            if ($ahora->lt($tiempoEsperado)) {
                                // Este es el despacho en curso (ya inició, aún no vence): no cerrar ni los siguientes
                                break;
                            }

                            $this->info("Finalizando despacho {$despacho->_id} (tiempo_esperado: {$tiempoEsperado})");
                            $response = $ctrl->end($fakeRequest, $despacho->_id);
                            $data = $response->getData(true);

                            if (isset($data['error']) && $data['error'] === false) {
                                $this->info(" Despacho {$despacho->_id} finalizado correctamente.");
                            } else {
                                $this->info(" Error al finalizar despacho {$despacho->_id}");
                            }

                        } catch (\Throwable $e) {
                            $this->info(" Error procesando despacho {$despacho->_id}: " . $e->getMessage());
                        }
                    }
                }
            }

            $this->info("Finalización automática completada.");

        } catch (\Exception $ex) {
            $errorMessage = $ex->getMessage();
            $this->error("Error general: " . $errorMessage);

        }

    }
}
