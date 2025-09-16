<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
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
            Redis::subscribe(['gps-channel'], function ($message) {
                $data = json_decode($message, true);

                if (!$data || empty($data['imei'])) {
                    Log::warning("GPS inválido recibido", ['data' => $data]);
                    return;
                }

                Log::info("Procesando GPS", $data);

                // === Aquí llamas tu misma lógica de notify() ===
                app(\App\Http\Controllers\RecorridoController::class)->notify(
                    new \Illuminate\Http\Request($data)
                );
            });
        } catch (\Exception $e) {
            Log::info("Error en ListenGps: " . $e->getMessage());
        }
    }
}
