<?php

namespace App;

use Moloquent as Model;

class LogPuntoVirtual extends Model
{
    protected $fillable = [
        'Trama', 'fecha_error', 'Error'
    ];
    protected $collection = 'LOGPUNTOVIRTUAL';

    protected $dates = [
        'fecha_error'
    ];
}
