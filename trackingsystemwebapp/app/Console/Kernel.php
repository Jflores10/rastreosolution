<?php

namespace App\Console;

use App\Console\Commands\ClearTramasLogsCommand;
use App\Console\Commands\FinalizarDespachosCommand;
use App\Console\Commands\ResetContPdAbiertaCommand;
use App\Console\Commands\ReiniciarKmDiarioCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
//use App\Console\Commands\ExportarATMCommand;
use App\Console\Commands\FinalizarDespachosDia;
use App\Console\Commands\SyncBloques;
use App\Console\Commands\ListenGps;


use App\Console\Commands\UpdateGPSAddress;
use App\Console\Commands\WriteLogSockets;
//use App\Commands\UpdateUnidadCommand;

//use App\Console\Commands\SyncConducDespaATMCommand;
//use App\Console\Commands\ImportRutaPocATMCommand;
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //ExportarATMCommand::class,
        FinalizarDespachosDia::class,
        WriteLogSockets::class,
        UpdateGPSAddress::class,
        FinalizarDespachosCommand::class,
        //UpdateUnidadCommand::class,
        ClearTramasLogsCommand::class,
        ResetContPdAbiertaCommand::class,
        ReiniciarKmDiarioCommand::class,
        SyncBloques::class,
        ListenGps::class
        //SyncConducDespaATMCommand::class,
        //ImportRutaPocATMCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //$schedule->command('command:atm')->withoutOverlapping();
        //$schedule->command('ts:finalizar-despachos')->everyMinute()->withoutOverlapping();
        //$schedule->command('ts:update-unidad-estado-ns')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('ts:reset-cont-pd-abierta')->dailyAt('04:00')->withoutOverlapping();
        // Km diario: corte al iniciar el día (00:00 local). A las 23:59 se perdería el
        // último minuto del día; las unidades con tramas ya se reinician solas en el parseador.
        $schedule->command('ts:reiniciar-km-diario')->dailyAt('00:00')->timezone('America/Guayaquil')->withoutOverlapping();
        //$schedule->command('ts:update-gps-address')->hourly()->withoutOverlapping();
        //$schedule->command('ts:clear-tramas-logs')->daily()->withoutOverlapping();
        //$schedule->command('bloques:sync')->everyMinute();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
