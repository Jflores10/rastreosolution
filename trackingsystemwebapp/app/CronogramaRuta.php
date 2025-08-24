<?php

namespace App;

use Moloquent As Model;

class CronogramaRuta extends Model
{
    protected $fillable = [
        'dia', 'desde', 'hasta'
    ];
    protected $dates = [
        'desde', 'hasta'  
    ];
}
