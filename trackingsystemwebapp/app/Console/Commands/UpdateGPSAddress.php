<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Recorrido;
use Carbon\Carbon;
use GuzzleHttp\Client;
use MongoDB\BSON\UTCDateTime;

class UpdateGPSAddress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ts:update-gps-address';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update GPS address for units based on their latitude and longitude';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private function getAddressFromGPS($latitude, $longitude) {
        $client = new Client();
        $urlFinal='http://geocoding.kimerasoftec.com/reverse?format=json&lat='.$latitude. '&lon='.$longitude;
        $res = $client->get($urlFinal, [
            'verify' => false
        ]);
        $code = $res->getStatusCode();
        if ($code === 200) {
            $json = json_decode($res->getBody());
            if (!isset($json->error))
            {
                $ubicacion=$json->display_name;
                return $ubicacion;
            }
            else
                return 'Error: ' . $json->error;
        }else{
            return null;
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    private function recalculateGpsAddress() {
        $fromDate = new Carbon('2025-06-16 00:00:00');
        $toDate = new Carbon('2025-06-19 05:59:59');
        $fini = new UTCDateTime(($fromDate->getTimestamp() * 1000)); //Inicio
        $ffin = new UTCDateTime(($toDate->getTimestamp() * 1000));
        $recorridos = Recorrido::where('gps_address', 'exists', false)
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->where('fecha_gps', '>=', $fini)
            ->where('fecha_gps', '<=', $ffin)
            ->whereIn('tipo', ['GTFRI'])
            ->orderBy('fecha_gps', 'desc') // Order by ID to ensure consistent processing
            ->get();

        $this->info('Starting to update GPS addresses for Recorridos: ' . $recorridos->count());

        foreach ($recorridos as $recorrido) {
            // Fetch as float (or false if not a valid number)
            $lat = filter_var($recorrido->latitud,  FILTER_VALIDATE_FLOAT);
            $lon = filter_var($recorrido->longitud, FILTER_VALIDATE_FLOAT);

            if (
                $lat === false || $lat < -90  || $lat > 90  ||
                $lon === false || $lon < -180 || $lon > 180
            ) {
                $this->error(
                    "Invalid GPS coordinates for Recorrido ID: {$recorrido->id}"
                . " with Lat: {$recorrido->latitud}, Lon: {$recorrido->longitud}"
                );
                continue;
            }

            
            // Check if gps_address is empty
            if (empty($recorrido->gps_address)) {
                // Fetch the address using the latitude and longitude
                $address = $this->getAddressFromGPS($recorrido->latitud, $recorrido->longitud);
                
                // Update the gps_address field
                if ($address === null) {
                    $this->error("Failed to fetch address for Recorrido ID: {$recorrido->id} with Lat: {$recorrido->latitud}, Lon: {$recorrido->longitud}");
                    continue; // Skip this record if address fetching fails
                }
                $recorrido->gps_address = $address;
                $recorrido->save();
            }
        }

        $this->info('GPS addresses updated successfully.');
    }
    private function cloneGpsAddressFromRepeatedCoordinates() { 
        $from = Carbon::createFromFormat('Y-m-d H:i:s', '2025-06-16 00:00:00');
        $to = Carbon::createFromFormat('Y-m-d H:i:s', '2025-06-18 23:59:59');
        date_add($from, date_interval_create_from_date_string('5 hours'));
        date_add($to, date_interval_create_from_date_string('5 hours'));
        $from = new UTCDateTime($from->getTimestamp() * 1000);
        $to = new UTCDateTime($to->getTimestamp() * 1000);
        //Please group by latitud and longitud, then update gps_address for all records with the same coordinates
        // Get all recorridos with a gps_address, grouped by latitud and longitud
        $recorridos = Recorrido::raw(function($collection) use ($from, $to) {
            return $collection->aggregate([
                ['$match' => [
                    'gps_address' => ['$exists' => true],
                    'latitud' => ['$ne' => null],
                    'longitud' => ['$ne' => null],
                    'tipo' => ['$in' => ['GTFRI']],
                    'fecha_gps'   => [
                        '$gte' => $from,
                        '$lte' => $to,
                    ]
                ]],
                ['$sort' => ['fecha_gps' => -1]],
                ['$group' => [
                    '_id' => ['latitud' => '$latitud', 'longitud' => '$longitud', 'gps_address' => '$gps_address'],
                ]]
            ]);
        });

        $this->info('Starting to clone GPS addresses for Recorridos: ' . count($recorridos));
        
        foreach ($recorridos as $recorrido) {
            // Fetch the address from the first record with the same coordinates
            $address = $recorrido['_id']['gps_address'];
            $lat = $recorrido['_id']['latitud'];
            $lon = $recorrido['_id']['longitud'];
            
            $this->info("Cloning GPS address: {$address} for coordinates Lat: {$lat}, Lon: {$lon}");
            // Update all records with the same coordinates
            Recorrido::where('latitud', $lat)
                ->where('longitud', $lon)
                ->where('gps_address', 'exists', false)
                ->where('tipo', 'GTFRI')
                ->where('fecha_gps', '>=', $from)
                ->where('fecha_gps', '<=', $to)
                ->update(['gps_address' => $address]);
            
            $this->info("Cloned GPS address: {$address} for coordinates Lat: {$lat}, Lon: {$lon}");
        }

        $this->info('GPS addresses cloned successfully.');    
    }

    public function fillPendingGpsAddress() {
        $this->info('Starting to recalculate GPS addresses...');
        $from = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 00:00:00'));
        $to = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d 23:59:59'));
        date_add($from, date_interval_create_from_date_string('5 hours'));
        date_add($to, date_interval_create_from_date_string('5 hours'));
        $from = new UTCDateTime($from->getTimestamp() * 1000);
        $to = new UTCDateTime($to->getTimestamp() * 1000);
        $recorridos = Recorrido::where('gps_address', 'exists', false)
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->where('fecha_gps', '>=', $from)
            ->where('fecha_gps', '<=', $to)
            ->whereIn('tipo', ['GTFRI'])
            ->orderBy('fecha_gps', 'desc') // Order by fecha_gps to ensure consistent processing
            ->get();
        
        $this->info('Starting to update GPS addresses for Recorridos: ' . $recorridos->count());
        foreach ($recorridos as $recorrido) {
            $gpsAddressExists = Recorrido::where('latitud', $recorrido->latitud)
                ->where('longitud', $recorrido->longitud)
                ->where('gps_address', 'exists', true)
                ->whereNotNull('gps_address')
                ->first();
            if ($gpsAddressExists) {
                $recorrido['gps_address'] = $gpsAddressExists->gps_address;
                $recorrido->save();
                $this->info("Updated Recorrido ID: {$recorrido->id} with GPS address: {$recorrido->gps_address}");
            } else {
                $this->error("No GPS address found for Recorrido ID: {$recorrido->id} with Lat: {$recorrido->latitud}, Lon: {$recorrido->longitud}");
                $gpsAddress = $this->getAddressFromGPS($recorrido->latitud, $recorrido->longitud);
                if ($gpsAddress) {
                    $recorrido['gps_address'] = $gpsAddress;
                    $recorrido->save();
                    $this->info("Updated Recorrido ID: {$recorrido->id} with GPS address: {$recorrido->gps_address}");
                } else {
                    $this->error("Failed to fetch address for Recorrido ID: {$recorrido->id} with Lat: {$recorrido->latitud}, Lon: {$recorrido->longitud}");
                }
            }
        }
    }

    public function handle()
    {
        $this->fillPendingGpsAddress();
    }
}
