<?php

namespace App;

use Moloquent as Model;

class LogErrorATMWS extends Model
{
    protected $collection = 'LogErrorATMWS';
    protected $fillable = [
        'Trama', 'fecha_error', 'Error'
    ];
    protected $dates = [
        'fecha_error'
    ];
}
