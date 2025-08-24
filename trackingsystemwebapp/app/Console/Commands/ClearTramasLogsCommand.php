<?php

namespace App\Console\Commands;

use App\Trama;
use Illuminate\Console\Command;

class ClearTramasLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ts:clear-tramas-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the logs of tramas';

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
        $this->info('Clearing tramas logs...');
        Trama::query()->delete();
        $this->info('Tramas logs cleared successfully.');
    }
}
