<?php

namespace App;

use Moloquent as Model;
class LOGATMDESPACHOS extends Model
{
    protected $collection = 'LOGATMDESPACHOS';
    protected $fillable = [
        'mensaje', 'fecha', 'localizacion'
    ];
    protected $dates = [
        'fecha'
    ];
}
