<?php

namespace App;

use Moloquent;
use Auth;
class Comando extends Moloquent
{
    protected $fillable = [
        'descripcion','comando','automatico','cooperativa_id',
        'buses','bloque'
    ];
   
    public function creador()
    {
        return $this->belongsTo('App\User');
    }
    public function modificador()
    {
        return $this->belongsTo('App\User');
    }

    public function cooperativa()
    {
        return $this->belongsTo('App\Cooperativa');
    }
    
}