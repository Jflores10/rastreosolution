<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\PuntoControl;
use App\Cooperativa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncBloques extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bloques:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Intercambia los bloques activos/inactivos ';

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
        $horaActual = Carbon::now()->format('H:i');

        // Buscar cooperativas cuya hora de actualización coincida
        $cooperativas = Cooperativa::where('hora_sync', $horaActual)->get();

        foreach ($cooperativas as $coop) {
            $this->info("Procesando cooperativa: {$coop->descripcion}");

            $puntos = PuntoControl::where('cooperativa_id', $coop->_id)
                                   ->where('pdi_padre', '!=', '')
                                   ->get();

            $agrupados = $puntos->groupBy('pdi_padre');

            foreach ($agrupados as $pdi_padre => $grupo) {
                if ($grupo->count() < 2) {
                    $this->info(" Saltando pdi_padre {$pdi_padre} (solo tiene un bloque)");
                    continue;
                }

                foreach ($grupo as $punto) {
                  
                    $estadoAnterior = $punto->activo ? 'activo' : 'inactivo';
                    $bloqueAnterior = $punto->bloque;

                    if ($punto->activo) {
                        $punto->activo = false;
                        $punto->pdi = '0' . $punto->pdi_padre;
                    } else {
                        $punto->activo = true;
                        $punto->pdi = $punto->pdi_padre;
                    }

                    $punto->save();

                    // Guardar log por cada punto
                    Log::info("Bloque actualizado | Cooperativa: {$coop->descripcion} | Punto: {$punto->descripcion} | Bloque: {$bloqueAnterior} | Estado: {$estadoAnterior} → " . ($punto->activo ? 'activo' : 'inactivo') . " | Fecha: " . Carbon::now());
                }
            }

            $this->info("✅ Bloques intercambiados para cooperativa {$coop->descripcion} | Total puntos: " . $puntos->count());
            Log::info("Bloques intercambiados | Cooperativa: {$coop->descripcion} | Total puntos: " . $puntos->count() . " | Fecha: " . Carbon::now());
        }
    }
}
