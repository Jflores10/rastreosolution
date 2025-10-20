<?php

namespace App\Console\Commands;

use App\Despacho;
use App\PuntoControl;
use App\Recorrido;
use App\Ruta;
use Carbon\Carbon;
use App\LOGATMDESPACHOS;
use Illuminate\Console\Command;
use MongoDB\BSON\ObjectID;
use App\SchedulerLog;
use MongoDB\BSON\UTCDateTime;
use App\TipoUsuario;
use App\User;
use Illuminate\Support\Facades\Auth;
use App\Cooperativa;
use App\Unidad;

class FinalizarDespachosDia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:despacho';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finalizacion de despachos del dia';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private function contains($needle, $haystack)
    {
        return strpos($haystack, $needle) !== false;
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

            $cooperativas = Cooperativa::where('finalizacion_automatica', true)
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

                // Obtener unidades activas de la cooperativa
                $unidades = Unidad::where('cooperativa_id', $cooperativa->_id)
                    ->where('estado', 'A')
                    ->get();

                if ($unidades->isEmpty()) {
                    continue;
                }

                // Obtener despachos pendientes del día actual
                $despachos = Despacho::with('ruta')
                    ->whereIn('unidad_id', $unidades->pluck('_id'))
                    ->where('estado', 'P')
                    ->where('fecha', '>=', $hoy_desde)
                    ->where('fecha', '<=', $hoy_hasta)
                    ->get();

                $this->info("  Despachos pendientes: " . $despachos->count());

                if ($despachos->isEmpty()) {
                    continue;
                }

                foreach ($despachos as $despacho) {
                    $this->info("  Finalizando despacho: {$despacho->_id}");

                    try {
                        $response = $ctrl->end($fakeRequest, $despacho->_id);
                        $data = $response->getData(true);

                        if (isset($data['error']) && $data['error'] === false) {
                            $this->info(" Despacho {$despacho->_id} finalizado correctamente.");
                        } else {
                            $this->warn(" Error al finalizar despacho {$despacho->_id}");
                        }

                    } catch (\Throwable $e) {
                        $this->error(" Error procesando despacho {$despacho->_id}: " . $e->getMessage());
                        \App\Models\LOGATMDESPACHOS::create([
                            'mensaje' => "TASK DESPACHO FIN SERVER ({$cooperativa->nombre}): " . $e->getMessage(),
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
                'mensaje' => "TASK DESPACHO FIN SERVER GENERAL: " . $errorMessage,
                'fecha' => Carbon::now(),
                'localizacion' => 'Command handle()'
            ]);
        }


    }

    private function getDistance($lat1, $lon1, $lat2, $lon2)
    {
        if ($lat1 != null && $lon1 != 1 && $lat2 != null && $lon2 != null) {
            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            return ($miles * 1.609344);
        }
        return 0;
    }
}
