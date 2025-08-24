<?php

namespace App;

use Moloquent as Model;
class LOGATMRUTASPOC extends Model
{
    protected $collection = 'LOGATMRUTASPOC';
    protected $fillable = [
        'mensaje', 'fecha'
    ];
    protected $dates = [
        'fecha'
    ];
}
