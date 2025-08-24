<?php

namespace App;

use Moloquent as Model;

class SchedulerLog extends Model
{
    protected $fillable = [
        'fecha'
    ];
    protected $dates = [
        'fecha'
    ];
}
