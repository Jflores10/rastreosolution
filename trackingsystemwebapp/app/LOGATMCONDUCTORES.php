<?php

namespace App;

use Moloquent as Model;
class LOGATMCONDUCTORES extends Model
{
    protected $collection = 'LOGATMCONDUCTORES';
    protected $fillable = [
        'mensaje', 'fecha'
    ];
    protected $dates = [
        'fecha'
    ];
}
