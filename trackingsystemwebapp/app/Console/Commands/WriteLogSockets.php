<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WriteLogSockets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ts:write-log-sockets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Write log sockets from parser';

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
        $host = config('app.tcp_host', null);
        $port = config('app.tcp_port', null);
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $message = "ADMIN;GTLOG";
        $file = storage_path('logs/sockets.log');
        //Create file if not exists
        if (!file_exists($file)) {
            $fp = fopen($file, 'w', '0777');
            if ($fp) {
                fclose($fp);
            } else {
                $this->error("Failed to create file.");
                return;
            }
        }
        if ($socket)
        {
          socket_set_option($socket, SOL_SOCKET, SO_SNDBUF, strlen($message));          //return $max;
          $result = socket_connect($socket, $host, $port);
          if ($result)
          {
            socket_write($socket, $message);
            // Listen for multiple messages
            while (true) {
                $buffer = socket_read($socket, 10000);
                if ($buffer === false) {
                    break; // Exit the loop if there is an error
                }
                // Process the received message
                //$this->info("Received: " . $buffer);
                //Write a file to append data  
                // if file exceeds 200MB, clear it
                $mb = 200;
                if (filesize($file) > $mb * 1024 * 1024) {
                    file_put_contents($file, ""); // Clear the file
                } 
                $fp = fopen($file, 'a');
                if ($fp) {
                    $date = date('Y-m-d H:i:s');
                    // Write the buffer and date to the file
                    fwrite($fp, "[$date] " . $buffer . "\n");
                    fclose($fp);
                } else {
                    $this->error("Failed to open file for writing.");
                }
            }
            socket_close($socket);
          }
        }
    }
}
