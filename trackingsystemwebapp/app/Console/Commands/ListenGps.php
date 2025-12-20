<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

use App\Unidad;


class ListenGps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
     protected $signature = 'gps:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Escucha datos GPS desde Redis y los procesa';

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
            // Redis subscribe bloquea y espera mensajes, no es necesario un while
            Redis::subscribe(['gps-channel'], function ($message) {
                $data = json_decode($message, true);

                if (!$data || empty($data['imei'])) {
                    Log::warning("GPS inválido recibido", ['data' => $data]);
                    return;
                }


                // Llamada a la lógica de tu controller
                app(\App\Http\Controllers\RecorridoController::class)->update_sentido(
                    new Request($data)
                );

                $unidad = Unidad::where('imei', $data['imei'])->first();
                if (!$unidad) return;

                $payload = [
                    '_id' => (string)$unidad->_id,
                    'latitud' => $unidad->latitud,
                    'longitud' => $unidad->longitud,
                    'velocidad' => $unidad->velocidad,
                    'sentido' => $unidad->sentido,
                    'fecha_gps' => $unidad->fecha_gps
                        ? $unidad->fecha_gps->toDateTime()->format('Y-m-d H:i:s')
                        : null,
                    'fecha' => $unidad->fecha
                        ? $unidad->fecha->toDateTime()->format('Y-m-d H:i:s')
                        : null,
                    'alerta_velocidad_message' => $unidad->alerta_velocidad_message,
                    'alerta_puerta_message' => $unidad->alerta_puerta_message,
                    'alerta_desconx_message' => $unidad->alerta_desconx_message,
                    'cooperativa_id' => (string)$unidad->cooperativa_id
                ];

                Redis::publish('gps-realtime', json_encode($payload));

            });
        } catch (\Exception $e) {
            Log::error("Error en ListenGps: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }

    }
}
