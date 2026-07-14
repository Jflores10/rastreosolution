<?php

namespace App\Console\Commands;

use App\Unidad;
use Illuminate\Console\Command;

class ResetContPdAbiertaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ts:reset-cont-pd-abierta';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reinicia contpdabierta a 0 en todas las unidades activas';

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
     * @return int
     */
    public function handle()
    {
        try {
            $result = Unidad::raw(function ($collection) {
                return $collection->updateMany(
                    [
                        'estado' => 'A',
                        'contpdabierta' => ['$gt' => 0],
                    ],
                    [
                        '$set' => [
                            'contpdabierta' => 0,
                        ],
                    ]
                );
            });

            if (is_object($result) && method_exists($result, 'getMatchedCount') && method_exists($result, 'getModifiedCount')) {
                $this->info('Coincidencias: ' . $result->getMatchedCount() . ', modificadas: ' . $result->getModifiedCount() . '.');
            } else {
                $this->info('Reinicio de contpdabierta completado.');
            }
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());

            return 1;
        }

        return 0;
    }
}
