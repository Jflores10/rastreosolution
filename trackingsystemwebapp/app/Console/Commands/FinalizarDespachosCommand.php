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
    protected $description = 'Finaliza los despachos pendientes de las unidades de transporte';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private function finalizarDespachosPendientes($cooperativa)
    {
        $date = Carbon::now()->subHours(2);

        date_sub($date, date_interval_create_from_date_string('5 hours'));

        $unidades = Unidad::where('cooperativa_id', $cooperativa->_id)
            ->where('estado', 'A')
            ->get();
        $despachos = Despacho::whereIn('unidad_id', $unidades->pluck('_id'))
            ->where('estado', 'P')
            ->where('fecha', '<=', $date)
            ->get();

        $distributorType = TipoUsuario::where('valor', '1')
            ->firstOrFail();
        $user = User::where('tipo_usuario_id', $distributorType->_id)
            ->firstOrFail();
        Auth::onceUsingId($user->_id);

        $ctrl = app()->make(DespachoController::class);
        $fakeRequest = Request::create('/', 'GET', []);

        foreach ($despachos as $despacho) {
            $this->info("Finalizando despacho: {$despacho->_id}");
            $ctrl->end($fakeRequest, $despacho->_id);
            $this->info("Despacho {$despacho->_id} finalizado.");
        }

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cooperativas = Cooperativa::where('finalizacion_automatica', true)
            ->where('estado', 'A')
            ->get();
        foreach ($cooperativas as $cooperativa) {
            $this->info("Finalizando despachos para la cooperativa: {$cooperativa->descripcion}");
            $this->finalizarDespachosPendientes($cooperativa);
            $this->info("Despachos finalizados para la cooperativa: {$cooperativa->descripcion}");
        }
        $this->info('Proceso de finalización de despachos completado.');
    }
}
