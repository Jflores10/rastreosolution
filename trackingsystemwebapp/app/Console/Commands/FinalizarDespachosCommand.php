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
    protected $description = 'Finaliza los despachos pendientes de las unidades de transporte despues de 10min de finalizacion de ruta';

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

                foreach ($despachos as $despacho) {
                    try {
                        $puntos = $despacho->puntos_control ?? [];
                        if (empty($puntos)) continue;

                        $ultimo = end($puntos);

                        if (!empty($ultimo['marca'])) {
                            $fechaMarca = Carbon::parse($ultimo['marca']);
                            $ahora = Carbon::now();

                            $fechaLimite = $fechaMarca->copy()->addMinutes(10);

                            if ($ahora->greaterThanOrEqualTo($fechaLimite)) {
                                $this->info("⏱ Finalizando despacho {$despacho->_id} (marca: {$fechaMarca}, límite: {$fechaLimite})");
                                $response = $ctrl->end($fakeRequest, $despacho->_id);
                                $data = $response->getData(true);

                                if (isset($data['error']) && $data['error'] === false) {
                                    $this->info(" Despacho {$despacho->_id} finalizado correctamente.");
                                } else {
                                    $this->warn(" Error al finalizar despacho {$despacho->_id}");
                                }
                            } 
                        }

                    } catch (\Throwable $e) {
                        $this->error(" Error procesando despacho {$despacho->_id}: " . $e->getMessage());
                        \App\Models\LOGATMDESPACHOS::create([
                            'mensaje' => "TASK DESPACHO FIN 10MIN: " . $e->getMessage(),
                            'fecha' => Carbon::now(),
                            'localizacion' => 'DespachoController@end'
                        ]);
                    }
                }
            }

            $this->info("Finalización automática completada.");

        } catch (\Exception $ex) {
            $errorMessage = $ex->getMessage();
            $this->error("Error general: " . $errorMessage);

            \App\Models\LOGATMDESPACHOS::create([
                'mensaje' => "TASK DESPACHO FIN GENERAL: " . $errorMessage,
                'fecha' => Carbon::now(),
                'localizacion' => 'Command handle()'
            ]);
        }

    }
}
